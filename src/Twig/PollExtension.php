<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Twig;

use App\Entity;
use Doctrine\Common\Collections;
use Symfony\Component\Form\FormView;
use Twig\Attribute\AsTwigFilter;

class PollExtension
{
    /**
     * Return an AnswerForm corresponding to a proposal in the given VoteForm.
     */
    #[AsTwigFilter('getAnswerFormForProposal')]
    public function getAnswerFormForProposal(FormView $voteForm, Entity\Proposal $proposal): FormView
    {
        if (!isset($voteForm->children['answers'])) {
            throw new \LogicException('Expected a VoteForm in argument.');
        }

        $answers = $voteForm->children['answers'];

        foreach ($answers->children as $childFormView) {
            if (!isset($childFormView->children['value'])) {
                continue;
            }

            $valueForm = $childFormView->children['value'];

            if (
                !isset($valueForm->vars['attr']['data-proposal-id']) ||
                $valueForm->vars['attr']['data-proposal-id'] !== $proposal->getId()
            ) {
                continue;
            }

            return $childFormView;
        }

        throw new \LogicException('Proposal not found');
    }

    /**
     * @param Entity\Proposal[]|Collections\Collection<int, Entity\Proposal> $proposals
     *
     * @return array<array{Entity\Date, Entity\Proposal[]}>
     */
    #[AsTwigFilter('groupDateProposals')]
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
    #[AsTwigFilter('groupAnswersByValues')]
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
}
