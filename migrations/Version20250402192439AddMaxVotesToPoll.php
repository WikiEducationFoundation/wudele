<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

// phpcs:disable Generic.Files.LineLength
final class Version20250402192439AddMaxVotesToPoll extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the max_votes column to the poll table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE poll ADD max_votes INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE poll DROP max_votes');
    }
}
