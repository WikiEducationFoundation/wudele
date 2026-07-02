<?php

// This file is part of Wudele, a fork of Pollaris.
// Copyright 2026 Wiki Education Foundation
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260702192350AddPollTimezoneAndProposalStartAt extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the poll timezone and the proposal start_at columns for timezone-aware slots';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE poll ADD timezone VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE proposal ADD start_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE poll DROP timezone');
        $this->addSql('ALTER TABLE proposal DROP start_at');
    }
}
