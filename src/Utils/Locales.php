<?php

// This file is part of Pollaris.
// Copyright 2022-2024 Probesys (Bileto)
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Utils;

class Locales
{
    public const DEFAULT_LOCALE = 'en_GB';

    public const SUPPORTED_LOCALES = ['en_GB', 'fr_FR', 'oc'];

    /**
     * @return array<string, string>
     */
    public static function getSupportedLanguages(): array
    {
        return [
            'en_GB' => 'English',
            'fr_FR' => 'Français',
            'oc' => 'Occitan',
        ];
    }

    public static function isAvailable(string $locale): bool
    {
        return in_array($locale, self::SUPPORTED_LOCALES);
    }
}
