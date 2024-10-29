<?php

// This file is part of Pollaris.
// Copyright 2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Tests\Controller;

use App\Tests\Helper;
use App\Tests\Factory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry;

class PollsControllerTest extends WebTestCase
{
    use Foundry\Test\Factories;
    use Foundry\Test\ResetDatabase;
    use Helper\CsrfHelper;

    public function testGetNewRendersCorrectly(): void
    {
        $client = static::createClient();

        $client->request(Request::METHOD_GET, '/polls/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Poll creation');
    }

    public function testPostNewCreatesAPoll(): void
    {
        $client = static::createClient();
        $title = 'My poll';
        $description = 'Description of my poll';

        $client->request(Request::METHOD_POST, '/polls/new', [
            'poll' => [
                '_token' => $this->getCsrf($client, 'poll'),
                'title' => $title,
                'description' => $description,
            ],
        ]);

        $poll = Factory\PollFactory::last();
        $this->assertNotNull($poll);
        $this->assertSame($title, $poll->getTitle());
        $this->assertSame($description, $poll->getDescription());
        $id = $poll->getId();
        $adminToken = $poll->getAdminToken();
        $this->assertSame(20, strlen($id ?? ''));
        $this->assertSame(20, strlen($adminToken ?? ''));
        $this->assertResponseRedirects("/polls/{$id}/{$adminToken}/proposals", 302);
    }
}
