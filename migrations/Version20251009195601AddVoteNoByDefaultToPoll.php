<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

// phpcs:disable Generic.Files.LineLength
final class Version20251009195601AddVoteNoByDefaultToPoll extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the vote_no_by_default column to the poll table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE poll ADD vote_no_by_default BOOLEAN DEFAULT false NOT NULL');
        // Stay coherent with the old polls, but the new ones will have this option set to false.
        $this->addSql('UPDATE poll SET vote_no_by_default = true');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE poll DROP vote_no_by_default');
    }
}
