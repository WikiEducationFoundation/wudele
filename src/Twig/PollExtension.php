<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Twig;

use App\Entity;
use App\Service;
use Doctrine\Common\Collections;
use Symfony\Component\Form\FormView;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class PollExtension extends AbstractExtension
{
    public function __construct(
        private Service\ProposalService $proposalService,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('proposalFullLabel', [$this, 'proposalFullLabel']),
            new TwigFilter('groupDateProposals', [$this, 'groupDateProposals']),
            new TwigFilter('groupAnswersByValues', [$this, 'groupAnswersByValues']),
            new TwigFilter('groupAnswerFormsByDate', [$this, 'groupAnswerFormsByDate']),
        ];
    }

    public function proposalFullLabel(Entity\Proposal $proposal): string
    {
        return $this->proposalService->getFullLabel($proposal);
    }

    /**
     * @param Entity\Proposal[]|Collections\Collection<int, Entity\Proposal> $proposals
     *
     * @return array<array{Entity\Date, Entity\Proposal[]}>
     */
    public function groupDateProposals(mixed $proposals): array
    {
        $datesAndChoices = [];

        foreach ($proposals as $proposal) {
            $date = $proposal->getDate();

            if (!$date || !$date->getValue()) {
                throw new \LogicException('Expecting a "date" proposal, but date is not set');
            }

            $dateKey = $date->getValue()->format('Y-m-d');

            if (!isset($datesAndChoices[$dateKey])) {
                $datesAndChoices[$dateKey] = [$date, []];
            }

            $datesAndChoices[$dateKey][1][] = $proposal;
        }

        return $datesAndChoices;
    }

    /**
     * @param Collections\Collection<int, Entity\Answer> $answers
     *
     * @return array<string, Entity\Answer[]>
     */
    public function groupAnswersByValues(Collections\Collection $answers): array
    {
        $answersByValues = [
            'yes' => [],
            'maybe' => [],
            'no' => [],
        ];

        foreach ($answers as $answer) {
            $answersByValues[$answer->getValue()][] = $answer;
        }

        return $answersByValues;
    }

    /**
     * @return array<string, FormView[]>
     */
    public function groupAnswerFormsByDate(FormView $formView): array
    {
        $formViewsByDates = [];

        foreach ($formView->children as $childFormView) {
            if (!isset($childFormView->children['value'])) {
                throw new \LogicException('The child form must contain a "value" field');
            }

            $valueForm = $childFormView->children['value'];

            if (!isset($valueForm->vars['attr']['data-date'])) {
                throw new \LogicException('The value field must contain a "data-date" attribute');
            }

            /** @var string */
            $date = $valueForm->vars['attr']['data-date'];

            if (!isset($formViewsByDates[$date])) {
                $formViewsByDates[$date] = [];
            }

            $formViewsByDates[$date][] = $childFormView;
        }

        return $formViewsByDates;
    }
}
