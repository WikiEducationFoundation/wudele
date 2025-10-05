<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Flow;

use App\Entity;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PollFlowBuilder
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function build(Entity\Poll $poll): Flow
    {
        if ($poll->isClassicPoll()) {
            return new ClassicPollFlow($poll, $this->urlGenerator);
        } else {
            return new DatePollFlow($poll, $this->urlGenerator);
        }
    }
}
