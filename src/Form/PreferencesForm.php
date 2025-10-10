<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Form;

use App\Utils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class PreferencesForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $languages = Utils\Locales::getSupportedLanguages();

        $builder->add('locale', Type\ChoiceType::class, [
            'choices' => Utils\Locales::SUPPORTED_LOCALES,
            'label' => new TranslatableMessage('forms.preferences.locale.label'),
            'choice_label' => function (string $choice) use ($languages): string {
                return $languages[$choice];
            },
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => new TranslatableMessage('forms.confirm'),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'class' => 'form--standard',
            ],
        ]);
    }
}
