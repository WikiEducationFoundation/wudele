<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Tests\Controller;

use App\Tests\Helper;
use App\Tests\Factory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Zenstruck\Foundry;

class VotesControllerTest extends WebTestCase
{
    use Foundry\Test\Factories;
    use Foundry\Test\ResetDatabase;
    use Helper\CsrfHelper;
    use Helper\FactoryHelper;

    public function testGetNewRendersCorrectly(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->completed()->create();

        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/votes/new");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Choose your preferences');
    }

    public function testGetNewFailsIfPollIsNotCompleted(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::createOne([
            'completedAt' => null,
        ]);

        $this->expectException(NotFoundHttpException::class);

        $client->catchExceptions(false);
        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/votes/new");
    }

    public function testPostNewCreatesAVote(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->completed()->create();
        $proposal = $poll->getProposals()->first();
        $name = 'Alix';

        $this->assertNotFalse($proposal);

        $client->request(Request::METHOD_POST, "/polls/{$poll->getId()}/votes/new", [
            'vote' => [
                '_token' => $this->getCsrf($client, 'vote'),
                'authorName' => $name,
                'answers' => [
                    ['value' => 'yes'],
                ],
            ],
        ]);

        $votes = Factory\VoteFactory::all();
        $this->assertSame(1, count($votes));
        $this->assertSame($name, $votes[0]->getAuthorName());
        $this->assertSame($poll->getId(), $votes[0]->getPoll()->getId());
        $answers = $votes[0]->getAnswers()->toArray();
        $this->assertSame(1, count($answers));
        $this->assertSame('yes', $answers[0]->getValue());
        $this->assertSame($proposal->getId(), $answers[0]->getProposal()?->getId());
    }

    public function testPostNewFailsIfCsrfIsInvalid(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->completed()->create();
        $proposal = $poll->getProposals()->first();
        $name = 'Alix';

        $client->request(Request::METHOD_POST, "/polls/{$poll->getId()}/votes/new", [
            'vote' => [
                '_token' => 'not the token',
                'authorName' => $name,
                'answers' => [
                    ['value' => 'yes'],
                ],
            ],
        ]);

        $this->assertSelectorTextContains('#vote_error', 'The CSRF token is invalid');
        Factory\VoteFactory::assert()->count(0);
    }

    public function testGetShowRendersCorrectly(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new([
            'title' => 'My poll',
        ])->completed()->create();
        $vote = Factory\VoteFactory::createOne([
            'poll' => $poll,
        ]);

        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/votes/{$vote->getId()}");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'My poll');
    }

    public function testGetShowFailsIfPollIdDoesNotMatch(): void
    {
        $client = static::createClient();
        $poll1 = Factory\PollFactory::new()->completed()->create();
        $poll2 = Factory\PollFactory::new()->completed()->create();
        $vote = Factory\VoteFactory::createOne([
            'poll' => $poll1,
        ]);

        $this->expectException(NotFoundHttpException::class);

        $client->catchExceptions(false);
        $client->request(Request::METHOD_GET, "/polls/{$poll2->getId()}/votes/{$vote->getId()}");
    }

    public function testGetEditRendersCorrectly(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->completed()->create();
        $vote = Factory\VoteFactory::createOne([
            'poll' => $poll,
        ]);

        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/votes/{$vote->getId()}/edit");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Edit your vote');
    }

    public function testGetEditFailsIfPollIdDoesNotMatch(): void
    {
        $client = static::createClient();
        $poll1 = Factory\PollFactory::new()->completed()->create();
        $poll2 = Factory\PollFactory::new()->completed()->create();
        $vote = Factory\VoteFactory::createOne([
            'poll' => $poll1,
        ]);

        $this->expectException(NotFoundHttpException::class);

        $client->catchExceptions(false);
        $client->request(Request::METHOD_GET, "/polls/{$poll2->getId()}/votes/{$vote->getId()}/edit");
    }

    public function testPostEditChangesTheVoteValues(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->completed()->create();
        $proposal = $poll->getProposals()->first();
        $oldName = 'Alix';
        $newName = 'Benedict';
        $oldValue = 'no';
        $newValue = 'yes';
        $vote = Factory\VoteFactory::createOne([
            'poll' => $poll,
            'authorName' => $oldName,
        ]);
        $answer = Factory\AnswerFactory::createOne([
            'vote' => $vote,
            'proposal' => $proposal,
            'value' => $oldValue,
        ]);

        $client->request(Request::METHOD_POST, "/polls/{$poll->getId()}/votes/{$vote->getId()}/edit", [
            'vote' => [
                '_token' => $this->getCsrf($client, 'vote'),
                'authorName' => $newName,
                'answers' => [
                    ['value' => $newValue],
                ],
            ],
        ]);

        $this->refresh($vote);
        $this->assertSame($newName, $vote->getAuthorName());
        $this->refresh($answer);
        $this->assertSame($newValue, $answer->getValue());
    }

    public function testPostEditFailsIfCsrfIsInvalid(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->completed()->create();
        $proposal = $poll->getProposals()->first();
        $oldName = 'Alix';
        $newName = 'Benedict';
        $oldValue = 'no';
        $newValue = 'yes';
        $vote = Factory\VoteFactory::createOne([
            'poll' => $poll,
            'authorName' => $oldName,
        ]);
        $answer = Factory\AnswerFactory::createOne([
            'vote' => $vote,
            'proposal' => $proposal,
            'value' => $oldValue,
        ]);

        $client->request(Request::METHOD_POST, "/polls/{$poll->getId()}/votes/{$vote->getId()}/edit", [
            'vote' => [
                '_token' => 'not the token',
                'authorName' => $newName,
                'answers' => [
                    ['value' => $newValue],
                ],
            ],
        ]);

        $this->assertSelectorTextContains('#vote_error', 'The CSRF token is invalid');
        $this->refresh($vote);
        $this->assertSame($oldName, $vote->getAuthorName());
        $this->refresh($answer);
        $this->assertSame($oldValue, $answer->getValue());
    }
}
