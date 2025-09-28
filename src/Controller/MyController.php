<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MyController extends BaseController
{
    #[Route('/my', name: 'my')]
    public function index(): Response
    {
        return $this->render('my/index.html.twig');
    }
}
