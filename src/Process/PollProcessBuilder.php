<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Process;

use App\Entity;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PollProcessBuilder
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function build(Entity\Poll $poll): Process
    {
        if ($poll->isClassicPoll()) {
            return new ClassicPollProcess($poll, $this->urlGenerator);
        } else {
            return new DatePollProcess($poll, $this->urlGenerator);
        }
    }
}
