<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Controller;

use App\Utils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LocaleController extends BaseController
{
    #[Route('/locale', name: 'edit locale')]
    public function edit(Request $request): Response
    {
        $locale = $request->request->getString('locale', $request->getLocale());

        $referer = $request->headers->get('Referer');
        if ($referer === null) {
            $referer = '/';
        }

        if ($request->isMethod('POST')) {
            if (!Utils\Locales::isAvailable($locale)) {
                return $this->redirect($referer);
            }

            $session = $request->getSession();
            $session->set('_locale', $locale);

            return $this->redirect($referer);
        }

        $locales = Utils\Locales::getSupportedLanguages();

        return $this->render('locale/_edit.html.twig', [
            'locales' => $locales,
            'locale' => $locale,
            'localeLabel' => $locales[$locale],
        ]);
    }
}
