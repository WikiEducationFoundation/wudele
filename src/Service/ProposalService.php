<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Service;

use App\Entity;

class ProposalService
{
    public function __construct(
        private DateTranslator $dateTranslator,
    ) {
    }

    public function getFullLabel(Entity\Proposal $proposal): string
    {
        $proposalLabel = $proposal->getLabel();

        if (!$proposalLabel) {
            throw new \LogicException('Proposal has no label');
        }

        $date = $proposal->getDate();

        if (!$date) {
            return $proposalLabel;
        }

        $value = $date->getValue();

        if (!$value) {
            throw new \LogicException('Proposal date has no value');
        }

        $format = 'EEEE d MMMM yyyy';
        $formattedDate = $this->dateTranslator->format($value, $format);

        return "{$formattedDate} — {$proposalLabel}";
    }
}
