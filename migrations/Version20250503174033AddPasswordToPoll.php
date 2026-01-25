<?php

// This file is part of Pollaris.
// Copyright 2024-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

// phpcs:disable Generic.Files.LineLength
final class Version20250503174033AddPasswordToPoll extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add password and is_password_for_votes_only to the poll table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE poll ADD password VARCHAR(255) DEFAULT '' NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE poll ADD is_password_for_votes_only BOOLEAN DEFAULT false NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE poll DROP password
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE poll DROP is_password_for_votes_only
        SQL);
    }
}
