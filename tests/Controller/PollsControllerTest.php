<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Tests\Controller;

use App\Security;
use App\Service;
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
        $name = 'Alix';
        $email = 'alix@example.org';

        $client->request(Request::METHOD_POST, '/polls/new', [
            'poll' => [
                '_token' => $this->getCsrf($client, 'poll'),
                'title' => $title,
                'description' => $description,
                'authorName' => $name,
                'authorEmail' => $email,
            ],
        ]);

        $poll = Factory\PollFactory::last();
        $this->assertSame($title, $poll->getTitle());
        $this->assertSame($description, $poll->getDescription());
        $this->assertSame($name, $poll->getAuthorName());
        $this->assertSame($email, $poll->getAuthorEmail());
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
        $name = 'Alix';

        $client->request(Request::METHOD_POST, '/polls/new?type=date', [
            'poll' => [
                '_token' => $this->getCsrf($client, 'poll'),
                'title' => $title,
                'authorName' => $name,
            ],
        ]);

        $poll = Factory\PollFactory::last();
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
        $name = 'Alix';
        $description = 'Description of my poll';

        $client->request(Request::METHOD_POST, '/polls/new', [
            'poll' => [
                '_token' => 'not the token',
                'title' => $title,
                'description' => $description,
                'authorName' => $name,
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

        $client->request(Request::METHOD_GET, "/polls/{$poll->getSlug()}");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'My poll');
    }

    public function testGetShowWithCustomSlugRendersCorrectly(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new([
            'title' => 'My poll',
            'slug' => 'my-slug',
        ])->completed()->create();

        $client->request(Request::METHOD_GET, '/polls/my-slug');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'My poll');
    }

    public function testGetShowDoesNotRedirectIfAuthenticatedToPasswordProtectedPoll(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new([
            'title' => 'My poll',
            'password' => 'secret',
            'isPasswordForVotesOnly' => false,
        ])->completed()->create();
        $session = $this->getSession($client);
        /** @var Security\PollSecurity */
        $pollSecurity = $client->getContainer()->get(Security\PollSecurity::class);
        $pollSecurity->authenticate($session, $poll);
        $session->save();

        $client->request(Request::METHOD_GET, "/polls/{$poll->getSlug()}");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'My poll');
    }

    public function testGetShowDoesNotRedirectIfProtectedPollAllowsToSeeResults(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new([
            'title' => 'My poll',
            'password' => 'secret',
            'isPasswordForVotesOnly' => true,
        ])->completed()->create();

        $client->request(Request::METHOD_GET, "/polls/{$poll->getSlug()}");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'My poll');
    }

    public function testGetShowRedirectsIfNotAuthenticatedToPasswordProtectedPoll(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new([
            'title' => 'My poll',
            'password' => 'secret',
            'isPasswordForVotesOnly' => false,
        ])->completed()->create();

        $client->request(Request::METHOD_GET, "/polls/{$poll->getSlug()}");

        $this->assertResponseRedirects("/polls/{$poll->getId()}/authenticate", 302);
    }

    public function testGetShowFailsIfPollIsNotComplete(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::createOne([
            'completedAt' => null,
        ]);

        $this->expectException(NotFoundHttpException::class);

        $client->catchExceptions(false);
        $client->request(Request::METHOD_GET, "/polls/{$poll->getSlug()}");
    }

    public function testPostShowCreatesAVote(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new([
            'authorEmail' => 'charlie@example.com',
        ])->completed()->create();
        $proposal = $poll->getProposals()->first();
        $name = 'Alix';

        $this->assertNotFalse($proposal);

        $client->request(Request::METHOD_POST, "/polls/{$poll->getSlug()}", [
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
        $this->assertEmailCount(1);
        $email = $this->getMailerMessage();
        $this->assertNotNull($email);
        $this->assertEmailTextBodyContains($email, $name);
    }

    public function testPostShowFailsIfMaxVoteIsReached(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new([
            'maxVotes' => 1,
        ])->completed()->create();
        $proposal = $poll->getProposals()->first();
        $vote = Factory\VoteFactory::createOne([
            'poll' => $poll,
        ]);
        $answer = Factory\AnswerFactory::createOne([
            'vote' => $vote,
            'proposal' => $proposal,
            'value' => 'yes',
        ]);
        $name = 'Alix';

        $client->request(Request::METHOD_POST, "/polls/{$poll->getSlug()}", [
            'vote' => [
                '_token' => $this->getCsrf($client, 'vote'),
                'authorName' => $name,
                'answers' => [
                    ['value' => 'yes'],
                ],
            ],
        ]);

        $this->assertSelectorTextContains(
            '#vote_answers_0_value_error',
            'There have already been 1 vote(s) for this proposal, you cannot vote for it.'
        );
        $votes = Factory\VoteFactory::all();
        $this->assertSame(1, count($votes));
    }

    public function testPostShowFailsIfRequiredPasswordIsIncorrect(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new([
            'password' => 'secret',
            'isPasswordForVotesOnly' => true,
        ])->completed()->create();
        $proposal = $poll->getProposals()->first();
        $name = 'Alix';

        $this->assertNotFalse($proposal);

        $client->request(Request::METHOD_POST, "/polls/{$poll->getSlug()}", [
            'vote' => [
                '_token' => $this->getCsrf($client, 'vote'),
                'authorName' => $name,
                'answers' => [
                    ['value' => 'yes'],
                ],
                'password' => 'not the password',
            ],
        ]);

        $this->assertSelectorTextContains('#vote_password_error', 'The password is incorrect');
        Factory\VoteFactory::assert()->count(0);
    }

    public function testPostShowFailsIfCsrfIsInvalid(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->completed()->create();
        $proposal = $poll->getProposals()->first();
        $name = 'Alix';

        $client->request(Request::METHOD_POST, "/polls/{$poll->getSlug()}", [
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

    public function testGetAuthenticateRendersCorrectly(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new([
            'title' => 'My poll',
            'password' => 'secret',
            'isPasswordForVotesOnly' => false,
        ])->completed()->create();

        $client->request(Request::METHOD_GET, "/polls/{$poll->getSlug()}/authenticate");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Authentication to a protected poll');
    }

    public function testGetAuthenticateRedirectsIfPollIsNotPasswordProtected(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new([
            'title' => 'My poll',
            'password' => '',
            'isPasswordForVotesOnly' => false,
        ])->completed()->create();

        $client->request(Request::METHOD_GET, "/polls/{$poll->getSlug()}/authenticate");

        $this->assertResponseRedirects("/polls/{$poll->getId()}", 302);
    }

    public function testGetAuthenticateRedirectsIfAlreadyAuthenticated(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new([
            'title' => 'My poll',
            'password' => 'secret',
            'isPasswordForVotesOnly' => false,
        ])->completed()->create();
        $session = $this->getSession($client);
        /** @var Security\PollSecurity */
        $pollSecurity = $client->getContainer()->get(Security\PollSecurity::class);
        $pollSecurity->authenticate($session, $poll);
        $session->save();

        $client->request(Request::METHOD_GET, "/polls/{$poll->getSlug()}/authenticate");

        $this->assertResponseRedirects("/polls/{$poll->getId()}", 302);
    }

    public function testPostAuthenticateAuthenticatesAndRedirects(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new([
            'title' => 'My poll',
            'password' => 'secret',
            'isPasswordForVotesOnly' => false,
        ])->completed()->create();

        $client->request(Request::METHOD_POST, "/polls/{$poll->getSlug()}/authenticate", [
            'poll_authentication' => [
                '_token' => $this->getCsrf($client, 'poll_authentication'),
                'password' => 'secret',
            ],
        ]);

        $this->assertResponseRedirects("/polls/{$poll->getId()}", 302);
        $session = $this->getSession($client);
        /** @var Security\PollSecurity */
        $pollSecurity = static::getContainer()->get(Security\PollSecurity::class);
        $this->assertTrue($pollSecurity->isAuthenticated($session, $poll));
    }

    public function testPostAuthenticateFailsIfPasswordIsInvalid(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new([
            'title' => 'My poll',
            'password' => 'secret',
            'isPasswordForVotesOnly' => false,
        ])->completed()->create();

        $client->request(Request::METHOD_POST, "/polls/{$poll->getSlug()}/authenticate", [
            'poll_authentication' => [
                '_token' => $this->getCsrf($client, 'poll_authentication'),
                'password' => 'not the password',
            ],
        ]);

        $this->assertSelectorTextContains('#poll_authentication_password_error', 'The password is incorrect');
        $session = $this->getSession($client);
        /** @var Security\PollSecurity */
        $pollSecurity = static::getContainer()->get(Security\PollSecurity::class);
        $this->assertFalse($pollSecurity->isAuthenticated($session, $poll));
    }

    public function testPostAuthenticateFailsIfCsrfIsInvalid(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new([
            'title' => 'My poll',
            'password' => 'secret',
            'isPasswordForVotesOnly' => false,
        ])->completed()->create();

        $client->request(Request::METHOD_POST, "/polls/{$poll->getSlug()}/authenticate", [
            'poll_authentication' => [
                '_token' => 'not the token',
                'password' => 'secret',
            ],
        ]);

        $this->assertSelectorTextContains('#poll_authentication_error', 'The CSRF token is invalid');
        $session = $this->getSession($client);
        /** @var Security\PollSecurity */
        $pollSecurity = static::getContainer()->get(Security\PollSecurity::class);
        $this->assertFalse($pollSecurity->isAuthenticated($session, $poll));
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
        $oldName = 'Alix';
        $newName = 'Charlie';
        $oldEmail = 'alix@example.org';
        $newEmail = 'charlie@example.org';
        $poll = Factory\PollFactory::new()->classic()->create([
            'title' => $oldTitle,
            'description' => $oldDescription,
            'authorName' => $oldName,
            'authorEmail' => $oldEmail,
        ]);

        $client->request(Request::METHOD_POST, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/edit", [
            'poll' => [
                '_token' => $this->getCsrf($client, 'poll'),
                'title' => $newTitle,
                'description' => $newDescription,
                'authorName' => $newName,
                'authorEmail' => $newEmail,
            ],
        ]);

        $this->refresh($poll);
        $this->assertSame($newTitle, $poll->getTitle());
        $this->assertSame($newDescription, $poll->getDescription());
        $this->assertSame($newName, $poll->getAuthorName());
        $this->assertSame($newEmail, $poll->getAuthorEmail());
        $this->assertResponseRedirects("/polls/{$poll->getId()}/{$poll->getAdminToken()}/proposals", 302);
    }

    public function testPostEditFailsIfCsrfIsInvalid(): void
    {
        $client = static::createClient();
        $oldTitle = 'The poll';
        $newTitle = 'My poll';
        $oldDescription = 'Outdated description';
        $newDescription = 'The new description';
        $oldName = 'Alix';
        $newName = 'Charlie';
        $oldEmail = 'alix@example.org';
        $newEmail = 'charlie@example.org';
        $poll = Factory\PollFactory::new()->classic()->create([
            'title' => $oldTitle,
            'description' => $oldDescription,
            'authorName' => $oldName,
            'authorEmail' => $oldEmail,
        ]);

        $client->request(Request::METHOD_POST, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/edit", [
            'poll' => [
                '_token' => 'not the token',
                'title' => $newTitle,
                'description' => $newDescription,
                'authorName' => $newName,
                'authorEmail' => $newEmail,
            ],
        ]);

        $this->assertSelectorTextContains('#poll_error', 'The CSRF token is invalid');
        $this->refresh($poll);
        $this->assertSame($oldTitle, $poll->getTitle());
        $this->assertSame($oldDescription, $poll->getDescription());
        $this->assertSame($oldName, $poll->getAuthorName());
        $this->assertSame($oldEmail, $poll->getAuthorEmail());
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
        $this->assertResponseRedirects("/polls/{$poll->getId()}/{$poll->getAdminToken()}/summary", 302);
    }

    public function testPostProposalsSynchronizesExistingVotesWithNewProposals(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->classic()->create();
        $existingProposal = Factory\ProposalFactory::createOne([
            'label' => 'Foo',
            'poll' => $poll,
        ]);
        $existingVote = Factory\VoteFactory::createOne([
            'poll' => $poll,
        ]);
        $existingAnswer = Factory\AnswerFactory::createOne([
            'vote' => $existingVote,
            'proposal' => $existingProposal,
            'value' => 'yes',
        ]);

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
        $this->assertSame('Bar', $proposals[1]->getLabel());
        $this->refresh($existingVote);
        $voteAnswers = $existingVote->getAnswers()->toArray();
        $this->assertSame(2, count($voteAnswers));
        $this->assertSame($proposals[0], $voteAnswers[0]->getProposal());
        $this->assertSame('yes', $voteAnswers[0]->getValue());
        $this->assertSame($proposals[1], $voteAnswers[1]->getProposal());
        $this->assertSame('', $voteAnswers[1]->getValue());
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
        $this->assertResponseRedirects("/polls/{$poll->getId()}/{$poll->getAdminToken()}/summary", 302);
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
        $this->assertResponseRedirects("/polls/{$poll->getId()}/{$poll->getAdminToken()}/summary", 302);
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

    public function testGetSettingsRendersCorrectly(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->withProposal()->create();

        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/settings");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Configure the poll');
    }

    public function testGetSettingsRedirectsIfThereAreNoProposals(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::createOne();

        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/settings");

        $this->assertResponseRedirects("/polls/{$poll->getId()}/{$poll->getAdminToken()}/proposals", 302);
    }

    public function testGetSettingsFailsIfAdminTokenDoesNotMatch(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->withProposal()->create();

        $this->expectException(NotFoundHttpException::class);

        $client->catchExceptions(false);
        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/not-the-token/settings");
    }

    public function testPostSettingsCanChangeOptions(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->withProposal()->create();
        $maxVotes = 1;
        $slug = 'my-slug';
        $password = 'secret';
        $notifyOnVotes = true;

        $client->request(Request::METHOD_POST, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/settings", [
            'poll_settings' => [
                '_token' => $this->getCsrf($client, 'poll_settings'),
                'maxVotes' => $maxVotes,
                'slug' => $slug,
                'isPasswordProtected' => true,
                'plainPassword' => [
                    'first' => $password,
                    'second' => $password,
                ],
                'notifyOnVotes' => $notifyOnVotes,
            ]
        ]);

        $this->refresh($poll);
        $this->assertSame($maxVotes, $poll->getMaxVotes());
        $this->assertSame($slug, $poll->getSlug());
        $this->assertTrue($poll->isNotifyOnVotes());
        /** @var Service\PollPassword */
        $pollPassword = static::getContainer()->get(Service\PollPassword::class);
        $this->assertTrue($pollPassword->verify($poll->getPassword() ?? '', $password));
    }

    public function testPostSettingsDoesNotChangePasswordIfNotSet(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new([
            'password' => 'secret',
        ])->withProposal()->create();
        $maxVotes = 1;
        $slug = 'my-slug';

        $client->request(Request::METHOD_POST, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/settings", [
            'poll_settings' => [
                '_token' => $this->getCsrf($client, 'poll_settings'),
                'maxVotes' => $maxVotes,
                'slug' => $slug,
                'isPasswordProtected' => true,
                'plainPassword' => [
                    'first' => '',
                    'second' => '',
                ],
            ]
        ]);

        $this->refresh($poll);
        /** @var Service\PollPassword */
        $pollPassword = static::getContainer()->get(Service\PollPassword::class);
        $this->assertTrue($pollPassword->verify($poll->getPassword() ?? '', 'secret'));
    }

    public function testPostSettingsRemovesPasswordIfIsPasswordProtectedIsNotSent(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new([
            'password' => 'secret',
        ])->withProposal()->create();
        $maxVotes = 1;
        $slug = 'my-slug';

        $client->request(Request::METHOD_POST, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/settings", [
            'poll_settings' => [
                '_token' => $this->getCsrf($client, 'poll_settings'),
                'maxVotes' => $maxVotes,
                'slug' => $slug,
                'plainPassword' => [
                    'first' => '',
                    'second' => '',
                ],
            ]
        ]);

        $this->refresh($poll);
        $this->assertFalse($poll->isPasswordProtected());
    }

    public function testPostSettingsFailsIfCsrfIsInvalid(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->withProposal()->create();
        $slug = 'my-slug';

        $client->request(Request::METHOD_POST, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/settings", [
            'poll_settings' => [
                '_token' => 'not the token',
                'slug' => $slug,
            ]
        ]);

        $this->assertSelectorTextContains('#poll_settings_error', 'The CSRF token is invalid');
        $this->refresh($poll);
        $this->assertSame($poll->getId(), $poll->getSlug());
    }

    public function testGetSummaryRendersCorrectly(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::new()->withProposal()->withAuthor()->create();

        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/summary");

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Summary of your poll');
    }

    public function testGetSummaryRedirectsIfThereAreNoProposals(): void
    {
        $client = static::createClient();
        $poll = Factory\PollFactory::createOne();

        $client->request(Request::METHOD_GET, "/polls/{$poll->getId()}/{$poll->getAdminToken()}/summary");

        $this->assertResponseRedirects("/polls/{$poll->getId()}/{$poll->getAdminToken()}/proposals", 302);
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
