<?php

// This file is part of Pollaris.
// Copyright 2024-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Form\Type;

use App\Entity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class SlotType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('startTime', Type\TimeType::class, [
            'mapped' => false,
            'required' => false,
            'widget' => 'single_text',
            'input' => 'datetime_immutable',
            'label' => new TranslatableMessage('forms.slot_type.start_time.label'),
        ]);

        $builder->add('label', Type\TextType::class, [
            'trim' => true,
            'empty_data' => '',
            'required' => false,
            'label' => new TranslatableMessage('forms.slot_type.label.label_pattern'),
            'attr' => [
                'maxlength' => Entity\Proposal::MAX_LABEL_LENGTH,
            ],
        ]);

        // Prefill the (unmapped) startTime field from the proposal startAt,
        // expressed in the time zone of the poll.
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event): void {
            $proposal = $event->getData();

            if (!$proposal instanceof Entity\Proposal) {
                return;
            }

            $startAt = $proposal->getStartAt();

            if ($startAt === null) {
                return;
            }

            $timezone = $proposal->getPoll()?->getTimezone();

            if ($timezone) {
                $startAt = $startAt->setTimezone(new \DateTimeZone($timezone));
            }

            // TimeType interprets its data in the platform default time zone,
            // so pass the poll wall-clock time as a naive value to avoid a
            // second conversion.
            $event->getForm()->get('startTime')->setData(
                new \DateTimeImmutable('1970-01-01 ' . $startAt->format('H:i')),
            );
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Entity\Proposal::class,
        ]);
    }
}
