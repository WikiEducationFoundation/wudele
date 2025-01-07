<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Process;

use App\Entity;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ClassicPollProcess extends Process
{
    /** @var string[] */
    protected array $steps = [
        'init',
        'proposals',
        'author',
        'end',
    ];

    public function __construct(
        private Entity\Poll $poll,
        private UrlGeneratorInterface $urlGenerator,
    ) {
        if (!$poll->isClassicPoll()) {
            throw new \LogicException('Poll must be of type "classic"');
        }
    }

    public function checkStep(string $stepName): bool
    {
        if ($stepName === 'init') {
            return $this->poll->isTitleSet();
        } elseif ($stepName === 'proposals') {
            return !$this->poll->getProposals()->isEmpty();
        } elseif ($stepName === 'author') {
            return $this->poll->isAuthorSet();
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
        } elseif ($stepName === 'proposals') {
            return $this->urlGenerator->generate('edit poll proposals', [
                'id' => $this->poll->getId(),
                'token' => $this->poll->getAdminToken(),
            ]);
        } elseif ($stepName === 'author') {
            return $this->urlGenerator->generate('edit poll author', [
                'id' => $this->poll->getId(),
                'token' => $this->poll->getAdminToken(),
            ]);
        } elseif ($stepName === 'end') {
            return $this->urlGenerator->generate('poll', [
                'id' => $this->poll->getId(),
            ]);
        } else {
            throw new \LogicException("{$stepName} is an invalid step name");
        }
    }
}
