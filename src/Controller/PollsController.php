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

class PollsController extends BaseController
{
    #[Route('/polls/choose', name: 'choose poll type')]
    public function choose(): Response
    {
        return $this->render('polls/choose.html.twig');
    }

    #[Route('/polls/find', name: 'find polls')]
    public function find(Request $request, Service\PollsFinder $pollsFinder): Response
    {
        $form = $this->createNamedForm('find_polls', Form\FindPollsForm::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();

            $pollsFinder->sendEmailLinks($email);

            return $this->redirectToRoute('find polls', [
                'success' => true,
            ]);
        }

        return $this->render('polls/find.html.twig', [
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

    #[Route('/polls/{slug:poll}', name: 'poll')]
    public function show(
        Entity\Poll $poll,
        Request $request,
        Repository\VoteRepository $voteRepository,
        Repository\CommentRepository $commentRepository,
        Security\PollSecurity $pollSecurity,
        EventDispatcherInterface $eventDispatcher,
    ): Response {
        if (!$poll->isCompleted()) {
            throw $this->createNotFoundException('The poll doesn’t exist (yet).');
        }

        if (!$pollSecurity->isAuthenticated($request->getSession(), $poll)) {
            return $this->redirectToRoute('authenticate poll', [
                'slug' => $poll->getSlug(),
            ]);
        }

        $displayMode = $request->query->get('display', 'list');

        $session = $request->getSession();
        $voteId = $session->get("vote-{$poll->getId()}");

        $voteForm = null;

        if (!$voteId) {
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
                    'display' => $displayMode,
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

            $this->addFlash('success', 'comment.created');

            return $this->redirectToRoute('poll', [
                'slug' => $poll->getSlug(),
                'display' => $displayMode,
            ]);
        }

        return $this->render('polls/show.html.twig', [
            'poll' => $poll,
            'voteId' => $voteId,
            'voteForm' => $voteForm,
            'commentForm' => $commentForm,
            'displayMode' => $displayMode,
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

        if (!$poll->isFullPasswordProtected() || $pollSecurity->isAuthenticated($request->getSession(), $poll)) {
            return $this->redirectToRoute('poll', [
                'slug' => $poll->getSlug(),
            ]);
        }

        $form = $this->createNamedForm('poll_authentication', Form\PollAuthenticationForm::class, options: [
            'poll' => $poll,
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $pollSecurity->authenticate($request->getSession(), $poll);

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
                $pollSecurity->authenticate($request->getSession(), $poll);
            }

            return $this->redirectToRoute('poll summary', [
                'id' => $poll->getId(),
                'token' => $poll->getAdminToken(),
            ]);
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
}
