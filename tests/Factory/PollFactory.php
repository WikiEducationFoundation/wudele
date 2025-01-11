<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Tests\Factory;

use App\Entity;
use App\Utils;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Entity\Poll>
 */
final class PollFactory extends PersistentObjectFactory
{
    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'title' => self::faker()->words(3, true),
            'type' => 'classic',
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }

    public static function class(): string
    {
        return Entity\Poll::class;
    }

    public function classic(): self
    {
        return $this->with(['type' => 'classic']);
    }

    public function date(): self
    {
        return $this->with(['type' => 'date']);
    }

    public function withProposal(): self
    {
        $proposal = ProposalFactory::new([
            'poll' => $this,
        ]);

        return $this
            ->classic()
            ->with([
                'proposals' => [$proposal],
            ]);
    }

    public function withDate(): self
    {
        $date = DateFactory::new([
            'poll' => $this,
        ]);

        return $this
            ->date()
            ->with([
                'dates' => [$date],
            ]);
    }

    public function withAuthor(): self
    {
        return $this->with([
            'authorName' => self::faker()->name(),
        ]);
    }

    public function completed(): self
    {
        return $this
            ->withProposal()
            ->withAuthor()
            ->with([
                'completedAt' => Utils\Time::now(),
            ]);
    }
}
