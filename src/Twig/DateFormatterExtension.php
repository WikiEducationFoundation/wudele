<?php

// This file is part of Pollaris.
// Copyright 2022-2024 Probesys (Bileto)
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Twig;

use App\Service;
use App\Utils;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class DateFormatterExtension extends AbstractExtension
{
    public function __construct(
        private Service\DateTranslator $dateTranslator,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('dateTrans', [$this, 'dateTrans']),
            new TwigFilter('dateIso', [$this, 'dateIso']),
            new TwigFilter('dateFull', [$this, 'dateFull']),
            new TwigFilter('dateShort', [$this, 'dateShort']),
        ];
    }

    public function dateTrans(\DateTimeInterface $date, string $format = 'dd MMM yyyy, HH:mm'): string
    {
        return $this->dateTranslator->format($date, $format);
    }

    public function dateIso(\DateTimeInterface $date): string
    {
        return $date->format(\DateTimeInterface::ATOM);
    }

    public function dateFull(\DateTimeInterface $date, bool $fullMonth = false, bool $cleverYear = false): string
    {
        $today = Utils\Time::relative('today');
        $currentYear = $today->format('Y');
        $dateYear = $date->format('Y');

        $format = 'dd';

        if ($fullMonth) {
            $format .= ' MMMM';
        } else {
            $format .= ' MMM';
        }

        if (!$cleverYear || $currentYear !== $dateYear) {
            $format .= ' yyyy';
        }

        $format .= ', HH:mm';

        return $this->dateTrans($date, $format);
    }

    public function dateShort(\DateTimeInterface $date, bool $fullMonth = false, bool $cleverYear = false): string
    {
        $today = Utils\Time::relative('today');
        $currentYear = $today->format('Y');
        $dateYear = $date->format('Y');

        $format = 'dd';

        if ($fullMonth) {
            $format .= ' MMMM';
        } else {
            $format .= ' MMM';
        }

        if (!$cleverYear || $currentYear !== $dateYear) {
            $format .= ' yyyy';
        }

        return $this->dateTrans($date, $format);
    }
}
