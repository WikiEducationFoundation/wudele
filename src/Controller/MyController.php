<?php

// This file is part of Pollaris.
// Copyright 2024-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Controller;

use App\Form;
use App\Service;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MyController extends BaseController
{
    #[Route('/my', name: 'my')]
    public function index(Request $request, Service\PollsFinder $pollsFinder): Response
    {
        $searchForm = $this->createNamedForm('search_polls', Form\SearchPollsForm::class);

        $searchForm->handleRequest($request);
        if ($searchForm->isSubmitted() && $searchForm->isValid()) {
            $email = $searchForm->get('email')->getData();

            $pollsFinder->sendEmailLinks($email);

            return $this->redirectToRoute('my', [
                'mailSent' => true,
            ]);
        }

        return $this->render('my/index.html.twig', [
            'searchForm' => $searchForm,
        ]);
    }
}
