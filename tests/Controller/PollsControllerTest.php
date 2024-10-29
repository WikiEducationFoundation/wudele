<?php

// This file is part of Pollaris.
// Copyright 2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Tests\Controller;

use App\Tests\Helper;
use App\Tests\Factory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Zenstruck\Foundry;

class PollsControllerTest extends WebTestCase
{
    use Foundry\Test\Factories;
    use Foundry\Test\ResetDatabase;
    use Helper\CsrfHelper;
    use Helper\FactoryHelper;

    public function testGetNewRendersCorrectly(): void
    {
        $client = static::createClient();

        $client->request(Request::METHOD_GET, '/polls/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Poll creation');
    }

    public function testPostNewCreatesAPoll(): void
    {
        $client = static::createClient();
        $title = 'My poll';
        $description = 'Description of my poll';

        $client->request(Request::METHOD_POST, '/polls/new', [
            'poll' => [
                '_token' => $this->getCsrf($client, 'poll'),
                'title' => $title,
                'description' => $description,
            ],
        ]);

        $poll = Factory\PollFactory::last();
        $this->assertNotNull($poll);
        $this->assertSame($title, $poll->getTitle());
        $this->assertSame($description, $poll->getDescription());
        $id = $poll->getId();
        $adminToken = $poll->getAdminToken();
        $this->assertSame(20, strlen($id ?? ''));
        $this->assertSame(20, strlen($adminToken ?? ''));
        $this->assertResponseRedirects("/polls/{$id}/{$adminToken}/proposals", 302);
    }

    public function testPostNewFailsIfCsrfIsInvalid(): void
    {
        $client = static::createClient();
        $title = 'My poll';
        $description = 'Description of my poll';

        $client->request(Request::METHOD_POST, '/polls/new', [
            'poll' => [
                '_token' => 'not the token',
                'title' => $title,
                'description' => $description,
            ],
        ]);

        $this->assertSelectorTextContains('#poll_error', 'The CSRF token is invalid');
        Factory\PollFactory::assert()->count(0);
    }

    public function testGetShowRendersCorrectly(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new([
            'title' => 'My poll',
        ])->created()->create();

        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'My poll');
    }

    public function testGetShowFailsIfAuthorNameIsEmpty(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::createOne([
            'title' => 'My poll',
            'authorName' => '',
        ]);
        $proposal = Factory\ProposalFactory::createOne([
            'poll' => $poll,
        ]);

        $this->expectException(NotFoundHttpException::class);

        $client->catchExceptions(false);
        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}");
    }

    public function testGetShowFailsIfProposalsAreEmpty(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::createOne([
            'title' => 'My poll',
            'authorName' => 'Alix',
        ]);

        $this->expectException(NotFoundHttpException::class);

        $client->catchExceptions(false);
        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}");
    }

    public function testGetProposalsRendersCorrectly(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::createOne();

        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/proposals");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Choose the proposals');
    }

    public function testGetProposalsFailsIfAdminTokenDoesNotMatch(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::createOne();

        $this->expectException(NotFoundHttpException::class);

        $client->catchExceptions(false);
        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/not-the-token/proposals");
    }

    public function testPostProposalsCreatesProposals(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::createOne();

        $client->request(Request::METHOD_POST, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/proposals", [
            'poll_proposals' => [
                '_token' => $this->getCsrf($client, 'poll_proposals'),
                'proposals' => [
                    ['label' => 'Foo'],
                    ['label' => 'Bar'],
                ],
            ],
        ]);

        $proposals = Factory\ProposalFactory::all();
        $this->assertSame(2, count($proposals));
        $this->assertSame('Foo', $proposals[0]->getLabel());
        $this->assertSame($poll->getId(), $proposals[0]->getPoll()?->getId());
        $this->assertSame('Bar', $proposals[1]->getLabel());
        $this->assertSame($poll->getId(), $proposals[1]->getPoll()?->getId());
    }

    public function testPostProposalsReplacesExistingProposals(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::createOne();
        $proposal1 = Factory\ProposalFactory::createOne([
            'label' => 'Bar',
            'poll' => $poll,
        ]);
        $proposal2 = Factory\ProposalFactory::createOne([
            'label' => 'Baz',
            'poll' => $poll,
        ]);

        $client->request(Request::METHOD_POST, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/proposals", [
            'poll_proposals' => [
                '_token' => $this->getCsrf($client, 'poll_proposals'),
                'proposals' => [
                    ['label' => 'Bar'],
                    ['label' => 'Foo'],
                ],
            ],
        ]);

        $proposals = Factory\ProposalFactory::all();
        $this->assertSame(2, count($proposals));
        $this->assertSame('Bar', $proposals[0]->getLabel());
        $this->assertSame($poll->getId(), $proposals[0]->getPoll()?->getId());
        $this->assertSame($proposal1->getId(), $proposals[0]->getId());
        $this->assertSame('Foo', $proposals[1]->getLabel());
        $this->assertSame($poll->getId(), $proposals[1]->getPoll()?->getId());
    }

    public function testPostProposalsFailsIfCsrfIsInvalid(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::createOne();

        $client->request(Request::METHOD_POST, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/proposals", [
            'poll_proposals' => [
                '_token' => 'not the token',
                'proposals' => [
                    ['label' => 'Foo'],
                    ['label' => 'Bar'],
                ],
            ],
        ]);

        $this->assertSelectorTextContains('#poll_proposals_error', 'The CSRF token is invalid');
        Factory\ProposalFactory::assert()->count(0);
    }

    public function testGetAuthorRendersCorrectly(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->withProposal()->create();

        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/author");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Who you are');
    }

    public function testGetAuthorRedirectsIfThereAreNoProposals(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::createOne();

        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/author");

        $this->assertResponseRedirects("/polls/{$poll->getId()}/{$poll->getAdminToken()}/proposals", 302);
    }

    public function testGetAuthorFailsIfAdminTokenDoesNotMatch(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->withProposal()->create();

        $this->expectException(NotFoundHttpException::class);

        $client->catchExceptions(false);
        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/not-the-token/author");
    }

    public function testPostAuthorChangesTheAuthorNameAndEmail(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->withProposal()->create();
        $name = 'Alix';
        $email = 'alix@example.org';

        $client->request(Request::METHOD_POST, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/author", [
            'poll_author' => [
                '_token' => $this->getCsrf($client, 'poll_author'),
                'authorName' => $name,
                'authorEmail' => $email,
            ]
        ]);

        $this->refresh($poll);
        $this->assertSame($name, $poll->getAuthorName());
        $this->assertSame($email, $poll->getAuthorEmail());
    }

    public function testPostAuthorFailsIfCsrfIsInvalid(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->withProposal()->create();
        $name = 'Alix';
        $email = 'alix@example.org';

        $client->request(Request::METHOD_POST, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/author", [
            'poll_author' => [
                '_token' => 'not the token',
                'authorName' => $name,
                'authorEmail' => $email,
            ]
        ]);

        $this->assertSelectorTextContains('#poll_author_error', 'The CSRF token is invalid');
        $this->refresh($poll);
        $this->assertSame('', $poll->getAuthorName());
        $this->assertSame('', $poll->getAuthorEmail());
    }
}
