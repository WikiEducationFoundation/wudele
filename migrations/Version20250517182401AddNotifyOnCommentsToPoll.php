<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

// phpcs:disable Generic.Files.LineLength
final class Version20250517182401AddNotifyOnCommentsToPoll extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the notify_on_comments column to the poll table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE poll ADD notify_on_comments BOOLEAN DEFAULT false NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE poll DROP notify_on_comments
        SQL);
    }
}
