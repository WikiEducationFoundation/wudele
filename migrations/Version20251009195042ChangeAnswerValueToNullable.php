<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

// phpcs:disable Generic.Files.LineLength
final class Version20251009195042ChangeAnswerValueToNullable extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change answer.value column to be nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE answer ALTER value DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE answer ALTER value SET NOT NULL');
    }
}
