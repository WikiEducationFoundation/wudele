<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Controller;

use App\Entity;
use App\Form;
use App\PollActivity;
use App\Process;
use App\Repository;
use App\Security;
use App\Service;
use App\Utils;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Contracts\Translation\TranslatorInterface;

class PollsController extends BaseController
{
    #[Route('/polls/choose', name: 'choose poll type')]
    public function choose(): Response
    {
        return $this->render('polls/choose.html.twig');
    }

    #[Route('/polls/search', name: 'search polls')]
    public function search(Request $request, Service\PollsFinder $pollsFinder): Response
    {
        $form = $this->createNamedForm('search_polls', Form\SearchPollsForm::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();

            $pollsFinder->sendEmailLinks($email);

            return $this->redirectToRoute('search polls', [
                'success' => true,
            ]);
        }

        return $this->render('polls/search.html.twig', [
            'form' => $form,
            'success' => $request->query->getBoolean('success'),
        ]);
    }

    #[Route('/polls/new', name: 'new poll')]
    public function new(
        Request $request,
        Repository\PollRepository $pollRepository,
        Process\PollProcessBuilder $pollProcessBuilder,
    ): Response {
        $type = $request->query->getString('type');

        if (!in_array($type, Entity\Poll::TYPES)) {
            $type = Entity\Poll::DEFAULT_TYPE;
        }

        $poll = new Entity\Poll();
        $poll->setType($type);

        $process = $pollProcessBuilder->build($poll);

        $form = $this->createNamedForm('poll', Form\PollForm::class, $poll);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $poll = $form->getData();

            $pollRepository->save($poll);

            return $this->redirect($process->getNextStepUrl('init'));
        }

        return $this->render('polls/new.html.twig', [
            'poll' => $poll,
            'form' => $form,
            'process' => $process,
        ]);
    }

    #[Route('/polls/{slug}.csv', name: 'poll csv')]
    public function showCsv(
        string $slug,
        Request $request,
        Repository\PollRepository $pollRepository,
        Security\PollSecurity $pollSecurity,
        TranslatorInterface $translator,
    ): Response {
        $poll = $pollRepository->loadBySlug($slug);

        if (!$poll || !$poll->isCompleted()) {
            throw $this->createNotFoundException('The poll doesn’t exist (yet).');
        }

        if (!$pollSecurity->isAuthenticated($poll)) {
            return $this->redirectToRoute('authenticate poll', [
                'slug' => $poll->getSlug(),
            ]);
        }

        if (!$pollSecurity->canViewResults($poll)) {
            throw $this->createNotFoundException('You cannot see the results of this poll.');
        }

        $data = [];

        if ($poll->isDatePoll()) {
            $proposalsByDates = $poll->getProposalsByDates();
            $allProposals = [];

            $rowDate = [''];

            foreach ($proposalsByDates as $dateIso => $dateAndProposals) {
                foreach ($dateAndProposals[1] as $proposal) {
                    $rowDate[] = $dateIso;
                    $allProposals[] = $proposal;
                }
            }

            $data[] = $rowDate;
        } else {
            $allProposals = $poll->getProposals();
        }

        $rowProposals = [''];

        foreach ($allProposals as $proposal) {
            $rowProposals[] = $proposal->getLabel();
        }

        $data[] = $rowProposals;

        foreach ($poll->getVotes() as $vote) {
            $voteRow = [$vote->getAuthorName()];

            foreach ($allProposals as $proposal) {
                $answer = $vote->getAnswerForProposal($proposal);
                if ($answer && $answer->getValue()) {
                    $voteRow[] = $translator->trans($answer->getHumanValue());
                } else {
                    $voteRow[] = '';
                }
            }

            $data[] = $voteRow;
        }

        $csvEncoder = new CsvEncoder();

        $csv = $csvEncoder->encode($data, 'csv', [
            'csv_escape_formulas' => true,
            'no_headers' => true,
        ]);

        $filename = $poll->getTitle() ?? '';
        $filename = str_replace(' ', '_', $filename);
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        if ($filename === null) {
            $filename = $poll->getSlug() ?? '';
        }
        $filename = preg_replace('/__+/', '_', $filename);
        if ($filename === null) {
            $filename = $poll->getSlug() ?? '';
        }
        $filename = trim($filename, '_') . '.csv';

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}\"");

        return $response;
    }

    #[Route('/polls/{slug}', name: 'poll')]
    public function show(
        string $slug,
        Request $request,
        Repository\PollRepository $pollRepository,
        Repository\VoteRepository $voteRepository,
        Repository\CommentRepository $commentRepository,
        Security\PollSecurity $pollSecurity,
        EventDispatcherInterface $eventDispatcher,
    ): Response {
        $poll = $pollRepository->loadBySlug($slug);

        if (!$poll || !$poll->isCompleted()) {
            throw $this->createNotFoundException('The poll doesn’t exist (yet).');
        }

        if (!$pollSecurity->isAuthenticated($poll)) {
            return $this->redirectToRoute('authenticate poll', [
                'slug' => $poll->getSlug(),
            ]);
        }

        $myVote = null;
        $voteForm = null;
        $commentForm = null;

        if (!$poll->isClosed()) {
            $session = $request->getSession();
            $voteId = $session->get("vote-{$poll->getId()}");

            if ($voteId) {
                $myVote = $voteRepository->find($voteId);
            } else {
                $vote = new Entity\Vote();
                $vote->setPoll($poll);
                $voteForm = $this->createNamedForm('vote', Form\VoteForm::class, $vote);

                $voteForm->handleRequest($request);
                if ($voteForm->isSubmitted() && $voteForm->isValid()) {
                    $vote = $voteForm->getData();

                    $voteRepository->save($vote);

                    $voteEvent = new PollActivity\VoteEvent($vote);
                    $eventDispatcher->dispatch($voteEvent, PollActivity\VoteEvent::NEW);

                    $session = $request->getSession();
                    $session->set("vote-{$poll->getId()}", $vote->getId());

                    $this->addFlash('success', 'vote.created');

                    return $this->redirectToRoute('poll', [
                        'slug' => $poll->getSlug(),
                    ]);
                }
            }

            $comment = new Entity\Comment();
            $comment->setPoll($poll);
            $commentForm = $this->createNamedForm('comment', Form\CommentForm::class, $comment);

            $commentForm->handleRequest($request);
            if ($commentForm->isSubmitted() && $commentForm->isValid()) {
                $comment = $commentForm->getData();

                $commentRepository->save($comment);

                $commentEvent = new PollActivity\CommentEvent($comment);
                $eventDispatcher->dispatch($commentEvent, PollActivity\CommentEvent::NEW);

                $this->addFlash('success', 'comment.created');

                return $this->redirectToRoute('poll', [
                    'slug' => $poll->getSlug(),
                ]);
            }
        }

        return $this->render('polls/show.html.twig', [
            'poll' => $poll,
            'myVote' => $myVote,
            'voteForm' => $voteForm,
            'commentForm' => $commentForm,
        ]);
    }

    #[Route('/polls/{slug:poll}/authenticate', name: 'authenticate poll')]
    public function authenticate(
        Entity\Poll $poll,
        Request $request,
        Security\PollSecurity $pollSecurity,
    ): Response {
        if (!$poll->isCompleted()) {
            throw $this->createNotFoundException('The poll doesn’t exist (yet).');
        }

        if (!$poll->isFullPasswordProtected() || $pollSecurity->isAuthenticated($poll)) {
            return $this->redirectToRoute('poll', [
                'slug' => $poll->getSlug(),
            ]);
        }

        $form = $this->createNamedForm('poll_authentication', Form\PollAuthenticationForm::class, options: [
            'poll' => $poll,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $pollSecurity->authenticate($poll);

            $this->addFlash('success', 'poll.authenticated');

            return $this->redirectToRoute('poll', [
                'slug' => $poll->getSlug(),
            ]);
        }

        return $this->render('polls/authenticate.html.twig', [
            'poll' => $poll,
            'form' => $form,
        ]);
    }

    #[Route('/polls/{id:poll}/{token}/edit', name: 'edit poll')]
    public function edit(
        Entity\Poll $poll,
        string $token,
        Request $request,
        Repository\PollRepository $pollRepository,
        Process\PollProcessBuilder $pollProcessBuilder,
    ): Response {
        if ($poll->getAdminToken() !== $token) {
            throw $this->createNotFoundException('The admin token doesn’t match.');
        }

        $process = $pollProcessBuilder->build($poll);

        $form = $this->createNamedForm('poll', Form\PollForm::class, $poll);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $poll = $form->getData();

            $pollRepository->save($poll);

            return $this->redirect($process->getNextStepUrl('init'));
        }

        return $this->render('polls/new.html.twig', [
            'poll' => $poll,
            'form' => $form,
            'process' => $process,
        ]);
    }

    #[Route('/polls/{id:poll}/{token}/settings', name: 'edit poll settings')]
    public function settings(
        Entity\Poll $poll,
        string $token,
        Request $request,
        Repository\PollRepository $pollRepository,
        Process\PollProcessBuilder $pollProcessBuilder,
        Security\PollSecurity $pollSecurity,
    ): Response {
        if ($poll->getAdminToken() !== $token) {
            throw $this->createNotFoundException('The admin token doesn’t match.');
        }

        $process = $pollProcessBuilder->build($poll);

        if (!$process->isAccessible('summary')) {
            return $this->redirect($process->getPreviousStepUrl('summary'));
        }

        $form = $this->createNamedForm('poll_settings', Form\PollSettingsForm::class, $poll);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $poll = $form->getData();

            $pollRepository->save($poll);

            if ($poll->isFullPasswordProtected()) {
                $pollSecurity->authenticate($poll);
            }

            return $this->redirect($process->getStepUrl('summary'));
        }

        return $this->render('polls/settings.html.twig', [
            'poll' => $poll,
            'form' => $form,
        ]);
    }

    #[Route('/polls/{id:poll}/{token}/proposals', name: 'edit poll proposals')]
    public function proposals(
        Entity\Poll $poll,
        string $token,
        Request $request,
        Repository\PollRepository $pollRepository,
        Process\PollProcessBuilder $pollProcessBuilder,
    ): Response {
        if ($poll->getAdminToken() !== $token) {
            throw $this->createNotFoundException('The admin token doesn’t match.');
        }

        if (!$poll->isClassicPoll()) {
            throw $this->createNotFoundException('The poll must be of type classic');
        }

        $process = $pollProcessBuilder->build($poll);

        if (!$process->isAccessible('proposals')) {
            return $this->redirect($process->getPreviousStepUrl('proposals'));
        }

        $form = $this->createNamedForm('poll_proposals', Form\PollProposalsForm::class, $poll);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $poll = $form->getData();

            $pollRepository->save($poll);

            return $this->redirect($process->getNextStepUrl('proposals'));
        }

        return $this->render('polls/proposals.html.twig', [
            'poll' => $poll,
            'form' => $form,
            'process' => $process,
        ]);
    }

    #[Route('/polls/{id:poll}/{token}/dates', name: 'edit poll dates')]
    public function dates(
        Entity\Poll $poll,
        string $token,
        Request $request,
        Repository\PollRepository $pollRepository,
        Process\PollProcessBuilder $pollProcessBuilder,
    ): Response {
        if ($poll->getAdminToken() !== $token) {
            throw $this->createNotFoundException('The admin token doesn’t match.');
        }

        if (!$poll->isDatePoll()) {
            throw $this->createNotFoundException('The poll must be of type date');
        }

        $process = $pollProcessBuilder->build($poll);

        if (!$process->isAccessible('dates')) {
            return $this->redirect($process->getPreviousStepUrl('dates'));
        }

        $form = $this->createNamedForm('poll_dates', Form\PollDatesForm::class, $poll);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $poll = $form->getData();

            $pollRepository->save($poll);

            return $this->redirect($process->getNextStepUrl('dates'));
        }

        return $this->render('polls/dates.html.twig', [
            'poll' => $poll,
            'form' => $form,
            'process' => $process,
        ]);
    }

    #[Route('/polls/{id:poll}/{token}/slots', name: 'edit poll slots')]
    public function slots(
        Entity\Poll $poll,
        string $token,
        Request $request,
        Repository\PollRepository $pollRepository,
        Process\PollProcessBuilder $pollProcessBuilder,
    ): Response {
        if ($poll->getAdminToken() !== $token) {
            throw $this->createNotFoundException('The admin token doesn’t match.');
        }

        if (!$poll->isDatePoll()) {
            throw $this->createNotFoundException('The poll must be of type date');
        }

        $process = $pollProcessBuilder->build($poll);

        if (!$process->isAccessible('slots')) {
            return $this->redirect($process->getPreviousStepUrl('slots'));
        }

        $form = $this->createNamedForm('poll_slots', Form\PollSlotsForm::class, $poll);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $poll = $form->getData();

            $pollRepository->save($poll);

            return $this->redirect($process->getNextStepUrl('slots'));
        }

        return $this->render('polls/slots.html.twig', [
            'poll' => $poll,
            'form' => $form,
            'process' => $process,
        ]);
    }

    #[Route('/polls/{id:poll}/{token}/summary', name: 'poll summary')]
    public function summary(
        Entity\Poll $poll,
        string $token,
        Request $request,
        Repository\PollRepository $pollRepository,
        Process\PollProcessBuilder $pollProcessBuilder,
        EventDispatcherInterface $eventDispatcher,
    ): Response {
        if ($poll->getAdminToken() !== $token) {
            throw $this->createNotFoundException('The admin token doesn’t match.');
        }

        $process = $pollProcessBuilder->build($poll);

        if (!$process->isAccessible('summary')) {
            return $this->redirect($process->getPreviousStepUrl('summary'));
        }

        $form = $this->createNamedForm('poll_summary', Form\PollSummaryForm::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $poll->setCompletedAt(Utils\Time::now());

            $pollRepository->save($poll);

            $pollEvent = new PollActivity\PollEvent($poll);
            $eventDispatcher->dispatch($pollEvent, PollActivity\PollEvent::COMPLETED);

            $session = $request->getSession();
            $session->set("admin-{$poll->getId()}", true);

            return $this->redirect($process->getNextStepUrl('summary'));
        }

        return $this->render('polls/summary.html.twig', [
            'poll' => $poll,
            'form' => $form,
            'process' => $process,
        ]);
    }

    #[Route('/polls/{id:poll}/{token}/complete', name: 'poll complete')]
    public function complete(
        Entity\Poll $poll,
        string $token,
        Process\PollProcessBuilder $pollProcessBuilder,
    ): Response {
        if ($poll->getAdminToken() !== $token) {
            throw $this->createNotFoundException('The admin token doesn’t match.');
        }

        $process = $pollProcessBuilder->build($poll);

        if (!$process->isAccessible('end')) {
            return $this->redirect($process->getPreviousStepUrl('end'));
        }

        return $this->render('polls/complete.html.twig', [
            'poll' => $poll,
            'process' => $process,
        ]);
    }

    #[Route('/polls/{id:poll}/{token}/admin', name: 'poll admin')]
    public function admin(
        Entity\Poll $poll,
        string $token,
        Request $request,
        Repository\PollRepository $pollRepository,
        Process\PollProcessBuilder $pollProcessBuilder,
    ): Response {
        if ($poll->getAdminToken() !== $token) {
            throw $this->createNotFoundException('The admin token doesn’t match.');
        }

        $process = $pollProcessBuilder->build($poll);

        if (!$process->isAccessible('end')) {
            return $this->redirect($process->getPreviousStepUrl('end'));
        }

        $session = $request->getSession();
        $session->set("admin-{$poll->getId()}", true);

        return $this->render('polls/admin.html.twig', [
            'poll' => $poll,
            'process' => $process,
        ]);
    }

    #[Route('/polls/{id:poll}/{token}/deletion', name: 'delete poll')]
    public function deletion(
        Entity\Poll $poll,
        string $token,
        Request $request,
        Repository\PollRepository $pollRepository,
    ): Response {
        if ($poll->getAdminToken() !== $token) {
            throw $this->createNotFoundException('The admin token doesn’t match.');
        }

        $form = $this->createNamedForm('poll_deletion', Form\PollDeletionForm::class, $poll);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $pollRepository->remove($poll, true);

            $this->addFlash('success', 'poll.deleted');

            return $this->redirectToRoute('home');
        }

        return $this->render('polls/deletion.html.twig', [
            'poll' => $poll,
            'form' => $form,
        ]);
    }
}
