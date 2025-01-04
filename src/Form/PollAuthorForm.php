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

class PollAuthorForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('authorName', Type\TextType::class, [
            'trim' => true,
            'empty_data' => '',
            'label' => new TranslatableMessage('forms.poll_author_form.author_name.label'),
            'attr' => [
                'maxlength' => Entity\Poll::MAX_AUTHOR_NAME_LENGTH,
            ],
        ]);

        $builder->add('authorEmail', Type\EmailType::class, [
            'required' => false,
            'trim' => true,
            'empty_data' => '',
            'label' => new TranslatableMessage('forms.poll_author_form.author_email.label'),
            'help' => new TranslatableMessage('forms.poll_author_form.author_email.help'),
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => new TranslatableMessage('forms.poll_author_form.submit.label'),
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
