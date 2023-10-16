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

namespace App\Form\GroupeGn;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\GroupeGn\GroupeGnForm.
 *
 * @author kevin
 */
class GroupeGnForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('gn', 'entity', [
            'label' => 'Jeu',
            'required' => true,
            'class' => \App\Entity\Gn::class,
            'property' => 'label',
        ])
            ->add('free', 'choice', [
                'label' => 'Groupe disponible ou réservé ?',
                'required' => false,
                'choices' => [
                    true => 'Groupe disponible',
                    false => 'Groupe réservé',
                ],
            ])
            ->add('code', 'text', [
                'required' => false,
            ])
            ->add('jeuStrategique', 'checkbox', [
                'label' => 'Participe au jeu stratégique',
                'required' => false,
            ])
            ->add('jeuMaritime', 'checkbox', [
                'label' => 'Participe au jeu maritime',
                'required' => false,
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\GroupeGn::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'groupeGn';
    }
}
