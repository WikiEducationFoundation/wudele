<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Tests\Twig;

use App\Twig\PollExtension;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PollExtensionTest extends WebTestCase
{
    public function testFlatten(): void
    {
        $pollExtension = new PollExtension();
        $arrays = [
            ['foo', 'bar', 'baz'],
            ['spam', 'ham', 'eggs'],
        ];

        $result = $pollExtension->flatten($arrays);

        $this->assertSame(['foo', 'bar', 'baz', 'spam', 'ham', 'eggs'], $result);
    }
}
