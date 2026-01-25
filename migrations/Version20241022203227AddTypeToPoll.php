<?php

// This file is part of Pollaris.
// Copyright 2024-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

// phpcs:disable Generic.Files.LineLength
final class Version20241022203227AddTypeToPoll extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the type column to the poll table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE poll ADD type VARCHAR(20) DEFAULT \'classic\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE poll DROP type');
    }
}
