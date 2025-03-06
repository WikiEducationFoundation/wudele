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

    public function testGetEditRendersCorrectly(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new([
            'title' => 'My poll',
        ])->completed()->create();
        $vote = Factory\VoteFactory::createOne([
            'poll' => $poll,
        ]);

        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/votes/{$vote->getId()}/edit");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'My poll');
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
