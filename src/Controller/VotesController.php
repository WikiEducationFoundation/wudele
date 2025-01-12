<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Controller;

use App\Entity;
use App\Form;
use App\Repository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VotesController extends BaseController
{
    #[Route('/polls/{pollId:poll}/votes/new', name: 'new vote')]
    public function new(
        #[MapEntity(mapping: ['poll' => 'id'])]
        Entity\Poll $poll,
        Request $request,
        Repository\VoteRepository $voteRepository,
    ): Response {
        if (!$poll->isCompleted()) {
            throw $this->createNotFoundException('The poll doesn’t exist (yet).');
        }

        $vote = new Entity\Vote();
        $vote->setPoll($poll);
        $form = $this->createNamedForm('vote', Form\VoteForm::class, $vote);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $vote = $form->getData();

            $voteRepository->save($vote);

            $session = $request->getSession();
            $session->set("vote-{$poll->getId()}", $vote->getId());

            $this->addFlash('success', 'vote.created');

            return $this->redirectToRoute('poll', [
                'id' => $poll->getId(),
            ]);
        }

        return $this->render('votes/new.html.twig', [
            'poll' => $poll,
            'form' => $form,
        ]);
    }

    #[Route('/polls/{pollId:poll}/votes/{id:vote}/edit', name: 'edit vote')]
    public function edit(
        #[MapEntity(mapping: ['poll' => 'id'])]
        Entity\Poll $poll,
        Entity\Vote $vote,
        Request $request,
        Repository\VoteRepository $voteRepository,
    ): Response {
        if ($poll->getId() !== $vote->getPoll()->getId()) {
            throw $this->createNotFoundException('Vote is not part of the poll');
        }

        $form = $this->createNamedForm('vote', Form\VoteForm::class, $vote);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $vote = $form->getData();

            $voteRepository->save($vote);

            $session = $request->getSession();
            $session->set("vote-{$poll->getId()}", $vote->getId());

            $this->addFlash('success', 'vote.updated');

            return $this->redirectToRoute('poll', [
                'id' => $poll->getId(),
            ]);
        }

        return $this->render('votes/edit.html.twig', [
            'poll' => $poll,
            'vote' => $vote,
            'form' => $form,
        ]);
    }
}
