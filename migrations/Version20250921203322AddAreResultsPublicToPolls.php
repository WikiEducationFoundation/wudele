<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250921203322AddAreResultsPublicToPolls extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the are_results_public column to the polls table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE poll ADD are_results_public BOOLEAN DEFAULT true NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE poll DROP are_results_public');
    }
}
