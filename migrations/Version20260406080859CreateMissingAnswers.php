<?php

// This file is part of Pollaris.
// Copyright 2024-2026 Marien Fressinaud
// Copyright 2026 Adrien Scholaert <adrien@framasoft.org>
// SPDX-License-Identifier: AGPL-3.0-or-later

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260406080859CreateMissingAnswers extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create missing answers for proposals added after votes have been made.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            INSERT INTO answer (proposal_id, vote_id, value, created_at, updated_at)
            SELECT p.id, v.id, '', NOW(), NOW()
            FROM proposal p
            INNER JOIN poll ON poll.id = p.poll_id
            INNER JOIN vote v ON v.poll_id = poll.id
            LEFT JOIN answer a ON a.proposal_id = p.id AND a.vote_id = v.id
            WHERE a.id IS NULL;
        SQL);
    }

    public function down(Schema $schema): void
    {
        // Do nothing on purpose
    }
}
