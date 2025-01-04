<?php

// This file is part of Pollaris.
// Copyright 2022-2024 Probesys (Bileto)
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Twig;

use Symfony\Component\Form\FormView;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FormExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('field_id', [$this, 'fieldId']),
        ];
    }

    /**
     * @param FormView|string $field
     */
    public function fieldId(mixed $field, string $suffix = ''): string
    {
        if ($field instanceof FormView) {
            $id = $field->vars['id'];
        } else {
            $id = $field;
        }

        if ($suffix) {
            return $id . '-' . $suffix;
        } else {
            return $id;
        }
    }
}
