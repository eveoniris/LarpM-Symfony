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

namespace App\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * LarpManager\Form\User\UserNewForm.
 *
 * @author kevin
 */
class UserNewForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', 'text', [
            'label' => 'Adresse mail',
            'required' => true,
        ])
            ->add('Username', 'text', [
                'label' => "Nom d'utilisateur",
                'required' => true,
            ])
            ->add('gn', 'entity', [
                'label' => 'Jeu auquel le nouvel utilisateur participe',
                'required' => false,
                'multiple' => false,
                'expanded' => true,
                'class' => \App\Entity\Gn::class,
                'property' => 'label',
            ])
            ->add('billet', 'entity', [
                'label' => 'Choisissez le billet a donner à cet utilisateur',
                'multiple' => false,
                'expanded' => true,
                'required' => false,
                'class' => \App\Entity\Billet::class,
                'property' => 'fullLabel',
                'query_builder' => static function ($er) {
                    $qb = $er->createQueryBuilder('b');
                    $qb->orderBy('b.gn', 'ASC');

                    return $qb;
                },
            ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'UserNew';
    }
}
