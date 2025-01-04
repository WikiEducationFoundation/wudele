<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

// phpcs:disable Generic.Files.LineLength
final class Version20241101153030AddDateToProposal extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the date_id column to the proposal table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE proposal ADD date_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE proposal ADD CONSTRAINT FK_BFE59472B897366B FOREIGN KEY (date_id) REFERENCES date (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_BFE59472B897366B ON proposal (date_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE proposal DROP CONSTRAINT FK_BFE59472B897366B');
        $this->addSql('DROP INDEX IDX_BFE59472B897366B');
        $this->addSql('ALTER TABLE proposal DROP date_id');
    }
}
