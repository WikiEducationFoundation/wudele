<?php

// This file is part of Pollaris.
// Copyright 2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Twig;

use App\Entity;
use App\Service;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ProposalExtension extends AbstractExtension
{
    public function __construct(
        private Service\ProposalService $proposalService,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('proposalFullLabel', [$this, 'proposalFullLabel']),
        ];
    }

    public function proposalFullLabel(Entity\Proposal $proposal): string
    {
        return $this->proposalService->getFullLabel($proposal);
    }
}
