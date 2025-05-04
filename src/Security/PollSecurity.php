<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Security;

use App\Entity;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PollSecurity
{
    public function __construct(
        #[Autowire('%kernel.secret%')]
        #[\SensitiveParameter]
        private string $secret,
    ) {
    }

    public function isAuthenticated(SessionInterface $session, Entity\Poll $poll): bool
    {
        if (!$poll->isFullPasswordProtected()) {
            return true;
        }

        $key = $this->generateKey($poll);
        $expectedHash = $this->generateHash($poll);
        $sessionHash = $session->get($key);

        if (!is_string($sessionHash)) {
            return false;
        }

        return hash_equals($expectedHash, $sessionHash);
    }

    public function authenticate(SessionInterface $session, Entity\Poll $poll): void
    {
        $key = $this->generateKey($poll);
        $hash = $this->generateHash($poll);

        $session->set($key, $hash);
    }

    private function generateKey(Entity\Poll $poll): string
    {
        return "poll-{$poll->getId()}-auth";
    }

    private function generateHash(Entity\Poll $poll): string
    {
        $data = "{$poll->getId()}:{$poll->getPassword()}";
        return hash_hmac('sha256', $data, $this->secret);
    }
}
