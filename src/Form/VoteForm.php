<?php

// This file is part of Pollaris.
// Copyright 2024-2025 Marien Fressinaud
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace App\Form;

use App\Entity;
use App\Validator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

class VoteForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('authorName', Type\TextType::class, [
            'trim' => true,
            'empty_data' => '',
            'label' => new TranslatableMessage('forms.vote_form.author_name.label'),
            'attr' => [
                'maxlength' => Entity\Vote::MAX_AUTHOR_NAME_LENGTH,
            ],
        ]);

        $builder->add('answers', Type\CollectionType::class, [
            'entry_type' => AnswerForm::class,
            'entry_options' => [
                'label' => false,
            ],
        ]);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => new TranslatableMessage('forms.vote_form.submit.label'),
            'attr' => [
                'class' => 'button--primary',
            ],
        ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $form = $event->getForm();
            $vote = $event->getData();

            $poll = $vote->getPoll();

            if ($poll->isVotePasswordProtected()) {
                $form->add('password', Type\PasswordType::class, [
                    'label' => new TranslatableMessage('forms.vote_form.password.label'),
                    'help' => new TranslatableMessage('forms.vote_form.password.help'),
                    'mapped' => false,
                    'constraints' => [
                        new Assert\NotBlank(
                            message: new TranslatableMessage('poll.password.required', domain: 'validators'),
                        ),
                        new Validator\PollPassword(
                            message: new TranslatableMessage('poll.password.incorrect', domain: 'validators'),
                            poll: $poll,
                        ),
                    ],
                ]);
            }

            if (count($vote->getAnswers()) > 0) {
                return;
            }

            $proposals = $poll->getProposals();

            foreach ($proposals as $proposal) {
                $answer = new Entity\Answer();
                $answer->setProposal($proposal);
                $vote->addAnswer($answer);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Entity\Vote::class,
        ]);
    }
}
