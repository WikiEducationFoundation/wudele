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

class PollsControllerTest extends WebTestCase
{
    use Foundry\Test\Factories;
    use Foundry\Test\ResetDatabase;
    use Helper\CsrfHelper;
    use Helper\FactoryHelper;

    public function testGetChooseRendersCorrectly(): void
    {
        $client = static::createClient();

        $client->request(Request::METHOD_GET, '/polls/choose');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Choose the type of poll');
    }

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
        $this->assertSame('classic', $poll->getType());
        $id = $poll->getId();
        $adminToken = $poll->getAdminToken();
        $this->assertSame(20, strlen($id ?? ''));
        $this->assertSame(20, strlen($adminToken ?? ''));
        $this->assertResponseRedirects("/polls/{$id}/{$adminToken}/proposals", 302);
    }

    public function testPostNewDatePollRedirectsToPollDates(): void
    {
        $client = static::createClient();
        $title = 'My poll';

        $client->request(Request::METHOD_POST, '/polls/new?type=date', [
            'poll' => [
                '_token' => $this->getCsrf($client, 'poll'),
                'title' => $title,
            ],
        ]);

        $poll = Factory\PollFactory::last();
        $this->assertNotNull($poll);
        $this->assertSame($title, $poll->getTitle());
        $this->assertSame('date', $poll->getType());
        $id = $poll->getId();
        $adminToken = $poll->getAdminToken();
        $this->assertResponseRedirects("/polls/{$id}/{$adminToken}/dates", 302);
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
        ])->completed()->create();

        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'My poll');
    }

    public function testGetShowFailsIfPollIsNotComplete(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::createOne([
            'completedAt' => null,
        ]);

        $this->expectException(NotFoundHttpException::class);

        $client->catchExceptions(false);
        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}");
    }

    public function testGetEditRendersCorrectly(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::createOne();

        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/edit");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Poll creation');
    }

    public function testGetEditFailsIfAdminTokenDoesNotMatch(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::createOne();

        $this->expectException(NotFoundHttpException::class);

        $client->catchExceptions(false);
        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/not-the-token/edit");
    }

    public function testPostEditChangesTheTitleAndDescription(): void
    {
        $client = static::createClient();
        $oldTitle = 'The poll';
        $newTitle = 'My poll';
        $oldDescription = 'Outdated description';
        $newDescription = 'The new description';
        $poll = Factory\PollFactory::new()->classic()->create([
            'title' => $oldTitle,
            'description' => $oldDescription,
        ]);

        $client->request(Request::METHOD_POST, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/edit", [
            'poll' => [
                '_token' => $this->getCsrf($client, 'poll'),
                'title' => $newTitle,
                'description' => $newDescription,
            ],
        ]);

        $this->refresh($poll);
        $this->assertSame($newTitle, $poll->getTitle());
        $this->assertSame($newDescription, $poll->getDescription());
        $this->assertResponseRedirects("/polls/{$poll->getId()}/{$poll->getAdminToken()}/proposals", 302);
    }

    public function testPostEditFailsIfCsrfIsInvalid(): void
    {
        $client = static::createClient();
        $oldTitle = 'The poll';
        $newTitle = 'My poll';
        $oldDescription = 'Outdated description';
        $newDescription = 'The new description';
        $poll = Factory\PollFactory::new()->classic()->create([
            'title' => $oldTitle,
            'description' => $oldDescription,
        ]);

        $client->request(Request::METHOD_POST, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/edit", [
            'poll' => [
                '_token' => 'not the token',
                'title' => $newTitle,
                'description' => $newDescription,
            ],
        ]);

        $this->assertSelectorTextContains('#poll_error', 'The CSRF token is invalid');
        $this->refresh($poll);
        $this->assertSame($oldTitle, $poll->getTitle());
        $this->assertSame($oldDescription, $poll->getDescription());
    }

    public function testGetProposalsRendersCorrectly(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->classic()->create();

        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/proposals");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Choose the proposals');
    }

    public function testGetProposalsFailsIfTypeIsDate(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->date()->create();

        $this->expectException(NotFoundHttpException::class);

        $client->catchExceptions(false);
        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/proposals");
    }

    public function testGetProposalsFailsIfAdminTokenDoesNotMatch(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->classic()->create();

        $this->expectException(NotFoundHttpException::class);

        $client->catchExceptions(false);
        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/not-the-token/proposals");
    }

    public function testPostProposalsCreatesProposals(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->classic()->create();

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
        $this->assertSame($poll, $proposals[0]->getPoll());
        $this->assertSame('Bar', $proposals[1]->getLabel());
        $this->assertSame($poll, $proposals[1]->getPoll());
        $this->assertResponseRedirects("/polls/{$poll->getId()}/{$poll->getAdminToken()}/author", 302);
    }

    public function testPostProposalsReplacesExistingProposals(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->classic()->create();
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
        $this->assertSame($poll, $proposals[0]->getPoll());
        $this->assertSame($proposal1, $proposals[0]);
        $this->assertSame('Foo', $proposals[1]->getLabel());
        $this->assertSame($poll, $proposals[1]->getPoll());
    }

    public function testPostProposalsFailsIfCsrfIsInvalid(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->classic()->create();

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

    public function testGetDatesRendersCorrectly(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->date()->create();

        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/dates");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Choose the dates');
    }

    public function testGetDatesFailsIfTypeIsClassic(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->classic()->create();

        $this->expectException(NotFoundHttpException::class);

        $client->catchExceptions(false);
        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/dates");
    }

    public function testGetDatesFailsIfAdminTokenDoesNotMatch(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->date()->create();

        $this->expectException(NotFoundHttpException::class);

        $client->catchExceptions(false);
        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/not-the-token/dates");
    }

    public function testPostDatesCreatesDates(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->date()->create();

        $client->request(Request::METHOD_POST, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/dates", [
            'poll_dates' => [
                '_token' => $this->getCsrf($client, 'poll_dates'),
                'dates' => [
                    ['value' => '2024-11-01'],
                    ['value' => '2024-11-02'],
                ],
            ],
        ]);

        $dates = Factory\DateFactory::all();
        $this->assertSame(2, count($dates));
        $this->assertSame('2024-11-01', $dates[0]->getValue()?->format('Y-m-d'));
        $this->assertSame($poll, $dates[0]->getPoll());
        $this->assertSame('2024-11-02', $dates[1]->getValue()?->format('Y-m-d'));
        $this->assertSame($poll, $dates[1]->getPoll());
        $this->assertResponseRedirects("/polls/{$poll->getId()}/{$poll->getAdminToken()}/slots", 302);
    }

    public function testPostDatesFailsIfCsrfIsInvalid(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->date()->create();

        $client->request(Request::METHOD_POST, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/dates", [
            'poll_dates' => [
                '_token' => 'not the token',
                'dates' => [
                    ['value' => '2024-11-01'],
                    ['value' => '2024-11-02'],
                ],
            ],
        ]);

        $this->assertSelectorTextContains('#poll_dates_error', 'The CSRF token is invalid');
        Factory\DateFactory::assert()->count(0);
    }

    public function testGetSlotsRendersCorrectly(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->withDate()->create();

        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/slots");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Choose the time slots');
    }

    public function testGetSlotsFailsIfTypeIsClassic(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->classic()->create();

        $this->expectException(NotFoundHttpException::class);

        $client->catchExceptions(false);
        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/slots");
    }

    public function testGetSlotsRedirectsIfThereAreNoDates(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->date()->create();

        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/slots");

        $this->assertResponseRedirects("/polls/{$poll->getId()}/{$poll->getAdminToken()}/dates", 302);
    }

    public function testGetSlotsFailsIfAdminTokenDoesNotMatch(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->withDate()->create();

        $this->expectException(NotFoundHttpException::class);

        $client->catchExceptions(false);
        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/not-the-token/slots");
    }

    public function testPostSlotsCreatesAProposal(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->date()->create();
        $date = Factory\DateFactory::createOne([
            'poll' => $poll,
        ]);
        $slot1 = '19h';
        $slot2 = '20h';

        $client->request(Request::METHOD_POST, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/slots", [
            'poll_slots' => [
                '_token' => $this->getCsrf($client, 'poll_slots'),
                'dates' => [
                    [
                        'proposals' => [
                            ['label' => $slot1],
                            ['label' => $slot2],
                        ],
                    ],
                ],
            ],
        ]);

        $this->refresh($poll);
        $proposals = $poll->getProposals()->toArray();
        $this->assertSame(2, count($proposals));
        $this->assertSame($slot1, $proposals[0]->getLabel());
        $this->assertSame($date, $proposals[0]->getDate());
        $this->assertSame($slot2, $proposals[1]->getLabel());
        $this->assertSame($date, $proposals[1]->getDate());
        $this->assertResponseRedirects("/polls/{$poll->getId()}/{$poll->getAdminToken()}/author", 302);
    }

    public function testPostSlotsCreatesADefaultProposalIfNoneArePosted(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->date()->create();
        $date = Factory\DateFactory::createOne([
            'poll' => $poll,
        ]);

        $client->request(Request::METHOD_POST, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/slots", [
            'poll_slots' => [
                '_token' => $this->getCsrf($client, 'poll_slots'),
                'dates' => [
                    [
                        'proposals' => [],
                    ],
                ],
            ],
        ]);

        $this->refresh($poll);
        $proposals = $poll->getProposals()->toArray();
        $this->assertSame(1, count($proposals));
        $this->assertSame('Day', $proposals[0]->getLabel());
        $this->assertSame($date, $proposals[0]->getDate());
        $this->assertResponseRedirects("/polls/{$poll->getId()}/{$poll->getAdminToken()}/author", 302);
    }

    public function testPostSlotsFailsIfCsrfTokenIsInvalid(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->date()->create();
        $date1 = Factory\DateFactory::createOne([
            'poll' => $poll,
        ]);
        $date2 = Factory\DateFactory::createOne([
            'poll' => $poll,
        ]);
        $slot1 = '19h';
        $slot2 = '20h';

        $client->request(Request::METHOD_POST, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/slots", [
            'poll_slots' => [
                '_token' => 'not the token',
                'dates' => [
                    [
                        'proposals' => [
                            ['label' => $slot1],
                            ['label' => $slot2],
                        ],
                    ],
                    [
                        'proposals' => [
                            ['label' => $slot2],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSelectorTextContains('#poll_slots_error', 'The CSRF token is invalid');
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

    public function testGetSummaryRendersCorrectly(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->withProposal()->withAuthor()->create();

        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/summary");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Summary of your poll');
    }

    public function testGetSummaryRedirectsIfThereAreNoAuthor(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->withProposal()->create();

        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/summary");

        $this->assertResponseRedirects("/polls/{$poll->getId()}/{$poll->getAdminToken()}/author", 302);
    }

    public function testGetSummaryFailsIfAdminTokenDoesNotMatch(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->withProposal()->withAuthor()->create();

        $this->expectException(NotFoundHttpException::class);

        $client->catchExceptions(false);
        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/not-the-token/summary");
    }

    public function testPostSummaryCompletesThePoll(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->withProposal()->withAuthor()->create();

        $client->request(Request::METHOD_POST, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/summary", [
            'poll_summary' => [
                '_token' => $this->getCsrf($client, 'poll_summary'),
            ]
        ]);

        $this->refresh($poll);
        $this->assertTrue($poll->isCompleted());
    }

    public function testPostSummaryFailsIfCsrfIsInvalid(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->withProposal()->withAuthor()->create();

        $client->request(Request::METHOD_POST, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/summary", [
            'poll_summary' => [
                '_token' => 'not the token',
            ]
        ]);

        $this->assertSelectorTextContains('#poll_summary_error', 'The CSRF token is invalid');
        $this->refresh($poll);
        $this->assertFalse($poll->isCompleted());
    }

    public function testGetCompleteRendersCorrectly(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->completed()->create();

        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/complete");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Your poll is ready');
    }

    public function testGetCompleteRedirectsIfNotCompleted(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->withProposal()->withAuthor()->create();

        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/complete");

        $this->assertResponseRedirects("/polls/{$poll->getId()}/{$poll->getAdminToken()}/summary", 302);
    }

    public function testGetCompleteFailsIfAdminTokenDoesNotMatch(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->completed()->create();

        $this->expectException(NotFoundHttpException::class);

        $client->catchExceptions(false);
        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/not-the-token/complete");
    }
}
