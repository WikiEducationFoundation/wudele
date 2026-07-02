<?php

// This file is part of Pollaris.
// Copyright 2024-2026 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Form;

use App\Entity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class PollSlotsForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('timezone', Type\TimezoneType::class, [
            'required' => false,
            'placeholder' => new TranslatableMessage('forms.poll_slots_form.timezone.placeholder'),
            'label' => new TranslatableMessage('forms.poll_slots_form.timezone.label'),
            'help' => new TranslatableMessage('forms.poll_slots_form.timezone.help'),
            'attr' => [
                'data-controller' => 'browser-timezone',
            ],
        ]);

        $builder->add('dates', Type\CollectionType::class, [
            'entry_type' => SlotsForm::class,
            'entry_options' => [
                'label' => false,
            ],
            'label' => false,
            'by_reference' => false,
        ]);

        // Turn the (unmapped) startTime fields into proposal startAt
        // timestamps, interpreted in the time zone of the poll. A slot with a
        // time gets its label derived from the entered wall-clock time so
        // that every consumer of labels keeps working. Without a poll time
        // zone, the time is a plain label: it reads the same everywhere.
        // The priority puts this listener before the validation listener, so
        // that derived labels satisfy the label NotBlank constraint.
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();
            $poll = $event->getData();

            if (!$poll instanceof Entity\Poll) {
                return;
            }

            $timezone = $poll->getTimezone();

            foreach ($form->get('dates') as $dateForm) {
                $date = $dateForm->getData();

                if (!$date instanceof Entity\Date || $date->getValue() === null) {
                    continue;
                }

                foreach ($dateForm->get('proposals') as $proposalForm) {
                    $proposal = $proposalForm->getData();
                    $startTime = $proposalForm->get('startTime')->getData();

                    if (!$proposal instanceof Entity\Proposal) {
                        continue;
                    }

                    if (!$startTime instanceof \DateTimeImmutable) {
                        $proposal->setStartAt(null);

                        continue;
                    }

                    $wallClockTime = $startTime->format('H:i');
                    $proposal->setLabel($wallClockTime);

                    if ($timezone) {
                        $proposal->setStartAt(new \DateTimeImmutable(
                            $date->getValue()->format('Y-m-d') . ' ' . $wallClockTime,
                            new \DateTimeZone($timezone),
                        ));
                    } else {
                        $proposal->setStartAt(null);
                    }
                }
            }
        }, priority: 100);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => [
                'class' => 'form--standard',
            ],
            'data_class' => Entity\Poll::class,
            'cascade_validation' => true,
        ]);
    }
}
