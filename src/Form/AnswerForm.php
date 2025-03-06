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

class AnswerForm extends AbstractType
{
    public function __construct(
        private Service\DateTranslator $dateTranslator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $form = $event->getForm();
            $answer = $event->getData();
            $proposal = $answer->getProposal();

            $date = $proposal->getDate();
            $proposalDate = '';
            if ($date && $date->getValue()) {
                $proposalDate = $this->dateTranslator->format($date->getValue(), 'EEEE d MMMM yyyy');
            }

            $form->add('value', Type\ChoiceType::class, [
                'choices' => Entity\Answer::VALID_VALUES,
                'label' => false,
                'empty_data' => 'no',
                'expanded' => true,

                'choice_label' => function (string $choice): TranslatableMessage {
                    return Entity\Answer::translateValue($choice);
                },

                'choice_attr' => function (string $choice): array {
                    return [
                        'class' => "radio--vote radio--vote-{$choice}",
                    ];
                },

                'attr' => [
                    'class' => 'cols cols--always cols--gap',
                    'data-proposal-id' => $proposal->getId(),
                    'data-date' => $proposalDate,
                ],

                'label_attr' => [
                    'class' => 'text--normal',
                ],
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Entity\Answer::class,
        ]);
    }
}
