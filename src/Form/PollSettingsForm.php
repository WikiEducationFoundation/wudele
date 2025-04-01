<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Form;

use App\Entity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

class PollSettingsForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('slug', Type\TextType::class, [
            'trim' => true,
            'empty_data' => '',
            'label' => new TranslatableMessage('forms.poll_settings_form.slug.label'),
            'help' => new TranslatableMessage('forms.poll_settings_form.slug.help'),
            'attr' => [
                'maxlength' => Entity\Poll::MAX_SLUG_LENGTH,
            ],
            'constraints' => [
                new Assert\NotBlank(
                    message: new TranslatableMessage('poll.slug.required', domain: 'validators'),
                )
            ],
            'block_prefix' => 'urlprefix',
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => new TranslatableMessage('forms.next'),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'class' => 'form--standard',
            ],
            'data_class' => Entity\Poll::class,
        ]);
    }
}
