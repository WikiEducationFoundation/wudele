<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Service;

use App\Repository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

class PollsFinder
{
    public function __construct(
        private Repository\PollRepository $pollRepository,
        private MailerInterface $mailer,
        private TranslatorInterface $translator,
    ) {
    }

    public function sendEmailLinks(string $email): void
    {
        $polls = $this->pollRepository->findBy(['authorEmail' => $email]);

        if (!$polls) {
            return;
        }

        $to = new Address($email);
        $locale = 'fr_FR';

        $subject = '[Pollaris] ';
        $subject .= $this->translator->trans('emails.polls_list.subject', locale: $locale);

        $email = (new TemplatedEmail())
            ->to($to)
            ->subject($subject)
            ->textTemplate('emails/polls_list.txt.twig')
            ->locale($locale)
            ->context([
                'polls' => $polls,
            ]);

        $this->mailer->send($email);
    }
}
