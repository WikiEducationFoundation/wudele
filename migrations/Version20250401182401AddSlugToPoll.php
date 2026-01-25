<?php

// This file is part of Pollaris.
// Copyright 2024-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

// phpcs:disable Generic.Files.LineLength
final class Version20250401182401AddSlugToPoll extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the slug column to poll';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE poll ADD slug VARCHAR(20)');
        $this->addSql('UPDATE poll SET slug = id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_84BCFA45989D9B62 ON poll (slug)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_84BCFA45989D9B62');
        $this->addSql('ALTER TABLE poll DROP slug');
    }
}
