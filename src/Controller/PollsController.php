<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Controller;

use App\Entity;
use App\Form;
use App\Repository;
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
    ): Response {
        $type = $request->query->getString('type');

        if (!in_array($type, Entity\Poll::TYPES)) {
            $type = Entity\Poll::DEFAULT_TYPE;
        }

        $poll = new Entity\Poll();
        $poll->setType($type);

        $form = $this->createNamedForm('poll', Form\PollForm::class, $poll);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $poll = $form->getData();

            $pollRepository->save($poll);

            if ($poll->isClassicPoll()) {
                return $this->redirectToRoute('edit poll proposals', [
                    'id' => $poll->getId(),
                    'token' => $poll->getAdminToken(),
                ]);
            } else {
                return $this->redirectToRoute('edit poll dates', [
                    'id' => $poll->getId(),
                    'token' => $poll->getAdminToken(),
                ]);
            }
        }

        return $this->render('polls/new.html.twig', [
            'poll' => $poll,
            'form' => $form,
            'currentStep' => 1,
        ]);
    }

    #[Route('/polls/{id:poll}', name: 'poll')]
    public function show(Entity\Poll $poll): Response
    {
        if (!$poll->isCreated()) {
            throw $this->createNotFoundException('The poll doesn’t exist (yet).');
        }

        return $this->render('polls/show.html.twig', [
            'poll' => $poll,
        ]);
    }

    #[Route('/polls/{id:poll}/{token}/proposals', name: 'edit poll proposals')]
    public function proposals(
        Entity\Poll $poll,
        string $token,
        Request $request,
        Repository\PollRepository $pollRepository,
    ): Response {
        if ($poll->getAdminToken() !== $token) {
            throw $this->createNotFoundException('The admin token doesn’t match.');
        }

        if ($poll->isDatePoll()) {
            return $this->redirectToRoute('edit poll slots', [
                'id' => $poll->getId(),
                'token' => $poll->getAdminToken(),
            ]);
        }

        $form = $this->createNamedForm('poll_proposals', Form\PollProposalsForm::class, $poll);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $poll = $form->getData();

            $pollRepository->save($poll);

            return $this->redirectToRoute('edit poll author', [
                'id' => $poll->getId(),
                'token' => $poll->getAdminToken(),
            ]);
        }

        return $this->render('polls/proposals.html.twig', [
            'poll' => $poll,
            'form' => $form,
            'currentStep' => 2,
        ]);
    }

    #[Route('/polls/{id:poll}/{token}/dates', name: 'edit poll dates')]
    public function dates(
        Entity\Poll $poll,
        string $token,
        Request $request,
        Repository\PollRepository $pollRepository,
    ): Response {
        if ($poll->getAdminToken() !== $token) {
            throw $this->createNotFoundException('The admin token doesn’t match.');
        }

        if ($poll->isClassicPoll()) {
            return $this->redirectToRoute('edit poll proposals', [
                'id' => $poll->getId(),
                'token' => $poll->getAdminToken(),
            ]);
        }

        $form = $this->createNamedForm('poll_dates', Form\PollDatesForm::class, $poll);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $poll = $form->getData();

            $pollRepository->save($poll);

            return $this->redirectToRoute('edit poll slots', [
                'id' => $poll->getId(),
                'token' => $poll->getAdminToken(),
            ]);
        }

        return $this->render('polls/dates.html.twig', [
            'poll' => $poll,
            'form' => $form,
            'currentStep' => 2,
        ]);
    }

    #[Route('/polls/{id:poll}/{token}/slots', name: 'edit poll slots')]
    public function slots(
        Entity\Poll $poll,
        string $token,
        Request $request,
        Repository\PollRepository $pollRepository,
    ): Response {
        if ($poll->getAdminToken() !== $token) {
            throw $this->createNotFoundException('The admin token doesn’t match.');
        }

        if ($poll->isClassicPoll()) {
            return $this->redirectToRoute('edit poll proposals', [
                'id' => $poll->getId(),
                'token' => $poll->getAdminToken(),
            ]);
        }

        if (count($poll->getDates()) === 0) {
            return $this->redirectToRoute('edit poll dates', [
                'id' => $poll->getId(),
                'token' => $poll->getAdminToken(),
            ]);
        }

        $form = $this->createNamedForm('poll_slots', Form\PollSlotsForm::class, $poll);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $poll = $form->getData();

            $pollRepository->save($poll);

            return $this->redirectToRoute('edit poll author', [
                'id' => $poll->getId(),
                'token' => $poll->getAdminToken(),
            ]);
        }

        return $this->render('polls/slots.html.twig', [
            'poll' => $poll,
            'form' => $form,
            'currentStep' => 3,
        ]);
    }

    #[Route('/polls/{id:poll}/{token}/author', name: 'edit poll author')]
    public function author(
        Entity\Poll $poll,
        string $token,
        Request $request,
        Repository\PollRepository $pollRepository,
    ): Response {
        if ($poll->getAdminToken() !== $token) {
            throw $this->createNotFoundException('The admin token doesn’t match.');
        }

        if (count($poll->getProposals()) === 0) {
            return $this->redirectToRoute('edit poll proposals', [
                'id' => $poll->getId(),
                'token' => $poll->getAdminToken(),
            ]);
        }

        $form = $this->createNamedForm('poll_author', Form\PollAuthorForm::class, $poll);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $poll = $form->getData();

            $pollRepository->save($poll);

            return $this->redirectToRoute('poll', [
                'id' => $poll->getId(),
            ]);
        }

        return $this->render('polls/author.html.twig', [
            'poll' => $poll,
            'form' => $form,
            'currentStep' => $poll->getTotalSteps(),
        ]);
    }
}
