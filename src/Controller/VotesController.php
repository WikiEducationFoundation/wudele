<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Controller;

use App\Entity;
use App\Form;
use App\Repository;
use App\Security;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VotesController extends BaseController
{
    #[Route('/polls/{slug:poll}/votes/{id:vote}/edit', name: 'edit vote')]
    public function edit(
        #[MapEntity(mapping: ['poll' => 'slug'])]
        Entity\Poll $poll,
        Entity\Vote $vote,
        Request $request,
        Repository\VoteRepository $voteRepository,
        Security\PollSecurity $pollSecurity,
    ): Response {
        if ($poll->getId() !== $vote->getPoll()->getId()) {
            throw $this->createNotFoundException('Vote is not part of the poll');
        }

        if (!$pollSecurity->isAuthenticated($request->getSession(), $poll)) {
            return $this->redirectToRoute('authenticate poll', [
                'slug' => $poll->getSlug(),
            ]);
        }

        $displayMode = $request->query->get('display', 'list');

        $session = $request->getSession();
        $hasAccessToAdmin = $session->get("admin-{$poll->getId()}");

        $form = $this->createNamedForm('vote', Form\VoteForm::class, $vote);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $vote = $form->getData();

            $voteRepository->save($vote);

            $session = $request->getSession();
            $session->set("vote-{$poll->getId()}", $vote->getId());

            $this->addFlash('success', 'vote.updated');

            return $this->redirectToRoute('poll', [
                'slug' => $poll->getSlug(),
                'display' => $displayMode,
            ]);
        }

        $comment = new Entity\Comment();
        $comment->setPoll($poll);
        $commentForm = $this->createNamedForm('comment', Form\CommentForm::class, $comment);

        return $this->render('polls/show.html.twig', [
            'poll' => $poll,
            'voteId' => $vote->getId(),
            'voteForm' => $form,
            'preserveScroll' => true,
            'commentForm' => $commentForm,
            'displayMode' => $displayMode,
            'hasAccessToAdmin' => $hasAccessToAdmin,
        ]);
    }

    #[Route('/polls/{pollId:poll}/{token}/votes/{voteId:vote}/deletion', name: 'delete vote', methods: ['POST'])]
    public function deletion(
        #[MapEntity(mapping: ['poll' => 'id'])]
        Entity\Poll $poll,
        #[MapEntity(mapping: ['vote' => 'id'])]
        Entity\Vote $vote,
        string $token,
        Request $request,
        Repository\VoteRepository $voteRepository,
    ): Response {
        if ($poll->getAdminToken() !== $token) {
            throw $this->createNotFoundException('The admin token doesn’t match.');
        }

        if ($poll->getId() !== $vote->getPoll()->getId()) {
            throw $this->createNotFoundException('Vote is not part of the poll');
        }

        $csrfToken = $request->request->getString('_csrf_token', '');

        if ($this->isCsrfTokenValid('delete vote', $csrfToken)) {
            $voteRepository->remove($vote, true);
        }

        return $this->redirectToRoute('poll admin', [
            'id' => $poll->getId(),
            'token' => $poll->getAdminToken(),
        ]);
    }
}
