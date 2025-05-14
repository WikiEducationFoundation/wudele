<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Process;

use App\Entity;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class DatePollProcess extends Process
{
    /** @var string[] */
    protected array $steps = [
        'init',
        'dates',
        'slots',
        'settings',
        'summary',
        'end',
    ];

    public function __construct(
        private Entity\Poll $poll,
        private UrlGeneratorInterface $urlGenerator,
    ) {
        if (!$poll->isDatePoll()) {
            throw new \LogicException('Poll must be of type "date"');
        }
    }

    public function checkStep(string $stepName): bool
    {
        if ($stepName === 'init') {
            return $this->poll->isTitleSet();
        } elseif ($stepName === 'dates') {
            return !$this->poll->getDates()->isEmpty();
        } elseif ($stepName === 'slots' || $stepName === 'settings') {
            return !$this->poll->getProposals()->isEmpty();
        } elseif ($stepName === 'summary') {
            return $this->poll->isCompleted();
        } else {
            throw new \LogicException("{$stepName} is an invalid step name");
        }
    }

    public function getStepUrl(string $stepName): string
    {
        if ($stepName === 'init') {
            return $this->urlGenerator->generate('edit poll', [
                'id' => $this->poll->getId(),
                'token' => $this->poll->getAdminToken(),
            ]);
        } elseif ($stepName === 'dates') {
            return $this->urlGenerator->generate('edit poll dates', [
                'id' => $this->poll->getId(),
                'token' => $this->poll->getAdminToken(),
            ]);
        } elseif ($stepName === 'slots') {
            return $this->urlGenerator->generate('edit poll slots', [
                'id' => $this->poll->getId(),
                'token' => $this->poll->getAdminToken(),
            ]);
        } elseif ($stepName === 'settings') {
            return $this->urlGenerator->generate('edit poll settings', [
                'id' => $this->poll->getId(),
                'token' => $this->poll->getAdminToken(),
            ]);
        } elseif ($stepName === 'summary') {
            return $this->urlGenerator->generate('poll summary', [
                'id' => $this->poll->getId(),
                'token' => $this->poll->getAdminToken(),
            ]);
        } elseif ($stepName === 'end') {
            return $this->urlGenerator->generate('poll complete', [
                'id' => $this->poll->getId(),
                'token' => $this->poll->getAdminToken(),
            ]);
        } else {
            throw new \LogicException("{$stepName} is an invalid step name");
        }
    }
}
