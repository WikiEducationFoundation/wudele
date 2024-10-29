<?php

// This file is part of Pollaris.
// Copyright 2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Tests\Factory;

use App\Entity;
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

    public function created(): self
    {
        return $this
            ->withProposal()
            ->with([
                'authorName' => self::faker()->name(),
            ]);
    }

    public function withProposal(): self
    {
        $proposal = ProposalFactory::new([
            'poll' => $this,
        ]);

        return $this->with([
            'proposals' => [$proposal],
        ]);
    }
}
