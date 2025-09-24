<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Security;

use App\Entity;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PollSecurity
{
    public function __construct(
        private RequestStack $requestStack,
        #[Autowire('%kernel.secret%')]
        #[\SensitiveParameter]
        private string $secret,
    ) {
    }

    public function hasAccessToAdmin(Entity\Poll $poll): bool
    {
        $session = $this->requestStack->getSession();
        return $session->get("admin-{$poll->getId()}") === true;
    }

    public function canViewResults(Entity\Poll $poll): bool
    {
        return $poll->areResultsPublic() || $this->hasAccessToAdmin($poll);
    }

    public function canEditVote(Entity\Poll $poll): bool
    {
        return (
            !$poll->isClosed() &&
            (
                $poll->getEditVoteMode() !== 'no' ||
                $this->hasAccessToAdmin($poll)
            )
        );
    }

    public function isAuthenticated(Entity\Poll $poll): bool
    {
        if (!$poll->isFullPasswordProtected()) {
            return true;
        }

        $session = $this->requestStack->getSession();

        $key = $this->generateKey($poll);
        $sessionHash = $session->get($key);

        if (!is_string($sessionHash)) {
            return false;
        }

        return $this->compareHash($poll, $sessionHash);
    }

    public function authenticate(Entity\Poll $poll): void
    {
        $key = $this->generateKey($poll);
        $hash = $this->generateHash($poll);

        $session = $this->requestStack->getSession();
        $session->set($key, $hash);
    }

    public function generateKey(Entity\Poll $poll): string
    {
        return "poll-{$poll->getId()}-auth";
    }

    public function generateHash(Entity\Poll $poll): string
    {
        $data = "{$poll->getId()}:{$poll->getPassword()}";
        return hash_hmac('sha256', $data, $this->secret);
    }

    public function compareHash(Entity\Poll $poll, string $sessionHash): bool
    {
        $expectedHash = $this->generateHash($poll);
        return hash_equals($expectedHash, $sessionHash);
    }
}
