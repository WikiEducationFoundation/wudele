<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Utils;

use Symfony\Component\Translation\TranslatableMessage;

// This file contains translations keys that are built dynamically.
// The translation:extract command cannot find them otherwise, and delete the
// keys from the translations files. By listing them manually in this file, the
// command detects them, even if this file is never used in the application.

// See templates/locale/_edit.html.twig
new TranslatableMessage('layout.locales.change.en_GB');
new TranslatableMessage('layout.locales.change.fr_FR');
