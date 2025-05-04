<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Form;

use App\Entity;
use App\Service;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

class PollSettingsForm extends AbstractType
{
    public function __construct(
        private Service\PollPassword $pollPassword,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('maxVotes', Type\IntegerType::class, [
            'label' => new TranslatableMessage('forms.poll_settings_form.max_votes.label'),
            'required' => false,
        ]);

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

        $builder->add('plainPassword', Type\RepeatedType::class, [
            'type' => Type\PasswordType::class,
            'required' => false,
            'first_options'  => [
                'label' => new TranslatableMessage('forms.poll_settings_form.password.label'),
            ],
            'second_options' => [
                'label' => new TranslatableMessage('forms.poll_settings_form.repeat_password.label'),
            ],
            'mapped' => false,
        ]);

        $builder->add('isPasswordForVotesOnly', Type\CheckboxType::class, [
            'label' => new TranslatableMessage('forms.poll_settings_form.password_for_votes_only.label'),
            'required' => false,
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => new TranslatableMessage('forms.next'),
        ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();
            $poll = $event->getData();

            $plainPassword = $form->get('plainPassword')->getData();

            if ($plainPassword) {
                $hashedPassword = $this->pollPassword->hash($plainPassword);
                $poll->setPassword($hashedPassword);
            }
        });
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
