<?php

// This file is part of Pollaris.
// Copyright 2024 Marien Fressinaud
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
    #[Route('/polls/new', name: 'new poll')]
    public function new(
        Request $request,
        Repository\PollRepository $pollRepository,
    ): Response {
        $poll = new Entity\Poll();
        $form = $this->createNamedForm('poll', Form\PollForm::class, $poll);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $poll = $form->getData();

            $pollRepository->save($poll);

            return $this->redirectToRoute('edit poll proposals', [
                'id' => $poll->getId(),
                'token' => $poll->getAdminToken(),
            ]);
        }

        return $this->render('polls/new.html.twig', [
            'form' => $form,
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
        ]);
    }
}
