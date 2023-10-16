<?php

/**
 * LarpManager - A Live Action Role Playing Manager
 * Copyright (C) 2016 Kevin Polez.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\NewMessageForm.
 *
 * @author kevin
 */
class NewMessageForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('title', 'text', [
            'required' => true,
            'label' => 'Titre',
            'attr' => [
                'placeholder' => 'Nouveau message',
            ],
        ])
            ->add('UserRelatedByDestinataire', 'entity', [
                'required' => true,
                'label' => 'Destinataire',
                'class' => \App\Entity\User::class,
                'property' => 'UserName',
                'attr' => [
                    'class' => 'selectpicker',
                    'data-live-search' => 'true',
                    'placeholder' => 'Destinataire',
                ],
                'query_builder' => static function ($er) {
                    $qb = $er->createQueryBuilder('u');
                    $qb->orderBy('u.Username', 'ASC');

                    return $qb;
                },
            ])
            ->add('text', 'textarea', [
                'required' => true,
                'label' => 'Message',
                'attr' => [
                    'rows' => 9,
                    'class' => 'tinymce',
                ],
            ]);
    }

    /**
     * Définition de la classe d'entité concernée.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\Message::class,
        ]);
    }

    /**
     * Nom du formlaire.
     */
    public function getName(): string
    {
        return 'newMessage';
    }
}
