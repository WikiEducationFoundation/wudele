<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Tests\Controller;

use App\Tests\Helper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;

class LocaleControllerTest extends WebTestCase
{
    use Helper\CsrfHelper;

    public function testPostEditSavesTheLocaleInTheSessionAndRedirects(): void
    {
        $client = static::createClient();
        $session = $this->getSession($client);

        $client->request(Request::METHOD_POST, '/locale', [
            'locale' => 'fr_FR',
        ]);

        $this->assertResponseRedirects('/', 302);
        $this->assertSame('fr_FR', $session->get('_locale'));
    }

    public function testPostUpdateLocaleFailsIfLocaleIsInvalid(): void
    {
        $client = static::createClient();
        $session = $this->getSession($client);

        $client->request(Request::METHOD_POST, '/locale', [
            'locale' => 'not a locale',
        ]);

        $this->assertResponseRedirects('/', 302);
        $this->assertNull($session->get('_locale'));
    }
}
