<?php

// This file is part of Pollaris.
// Copyright 2024 Marien Fressinaud
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
        $vote = new Entity\Vote();
        $vote->setPoll($poll);
        $form = $this->createNamedForm('vote', Form\VoteForm::class, $vote);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $vote = $form->getData();

            $voteRepository->save($vote);

            return $this->redirectToRoute('vote', [
                'pollId' => $poll->getId(),
                'id' => $vote->getId(),
            ]);
        }

        return $this->render('votes/new.html.twig', [
            'poll' => $poll,
            'form' => $form,
        ]);
    }

    #[Route('/polls/{pollId:poll}/votes/{id:vote}', name: 'vote')]
    public function show(
        #[MapEntity(mapping: ['poll' => 'id'])]
        Entity\Poll $poll,
        Entity\Vote $vote,
    ): Response {
        if ($poll->getId() !== $vote->getPoll()->getId()) {
            throw $this->createNotFoundException('Vote is not part of the poll');
        }

        return $this->render('votes/show.html.twig', [
            'poll' => $poll,
            'vote' => $vote,
        ]);
    }
}
