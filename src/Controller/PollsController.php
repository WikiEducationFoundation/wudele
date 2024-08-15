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

            return $this->redirectToRoute('poll', [
                'id' => $poll->getId(),
            ]);
        }

        return $this->render('polls/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/polls/{id:poll}', name: 'poll')]
    public function show(Entity\Poll $poll): Response
    {
        return $this->render('polls/show.html.twig', [
            'poll' => $poll,
        ]);
    }
}
