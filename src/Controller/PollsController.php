<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Controller;

use App\Entity;
use App\Form;
use App\Process;
use App\Repository;
use App\Utils;
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

    #[Route('/polls/{id:poll}', name: 'poll')]
    public function show(Entity\Poll $poll, Request $request): Response
    {
        if (!$poll->isCompleted()) {
            throw $this->createNotFoundException('The poll doesn’t exist (yet).');
        }

        $session = $request->getSession();
        $voteId = $session->get("vote-{$poll->getId()}");

        return $this->render('polls/show.html.twig', [
            'poll' => $poll,
            'voteId' => $voteId,
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

    #[Route('/polls/{id:poll}/{token}/author', name: 'edit poll author')]
    public function author(
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

        if (!$process->isAccessible('author')) {
            return $this->redirect($process->getPreviousStepUrl('author'));
        }

        $form = $this->createNamedForm('poll_author', Form\PollAuthorForm::class, $poll);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $poll = $form->getData();

            $pollRepository->save($poll);

            return $this->redirect($process->getNextStepUrl('author'));
        }

        return $this->render('polls/author.html.twig', [
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
