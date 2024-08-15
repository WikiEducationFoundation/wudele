<?php

// This file is part of Pollaris.
// Copyright 2024 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

// phpcs:disable Generic.Files.LineLength
final class Version20240814210246CreatePoll extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the poll table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE poll (id VARCHAR(20) NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, admin_token VARCHAR(20) NOT NULL, title VARCHAR(200) NOT NULL, description TEXT NOT NULL, PRIMARY KEY(id))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE poll');
    }
}
