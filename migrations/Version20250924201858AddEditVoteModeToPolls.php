<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250924201858AddEditVoteModeToPolls extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the edit_vote_mode column to the polls table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE poll ADD edit_vote_mode VARCHAR(20) DEFAULT \'own\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE poll DROP edit_vote_mode');
    }
}
