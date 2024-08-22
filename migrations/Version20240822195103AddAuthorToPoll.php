<?php

// This file is part of Pollaris.
// Copyright 2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

// phpcs:disable Generic.Files.LineLength
final class Version20240822195103AddAuthorToPoll extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the author_name and author_email columns to the poll table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE poll ADD author_name VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE poll ADD author_email VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE poll DROP author_name');
        $this->addSql('ALTER TABLE poll DROP author_email');
    }
}
