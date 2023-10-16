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

namespace App\Form\Item;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Monnaie\ItemForm.
 *
 * @author kevin
 */
class ItemLinkForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('personnage', 'entity', [
            'required' => false,
            'mapped' => false,
            'label' => 'Personnage',
            'class' => \App\Entity\Personnage::class,
            'property' => 'nom',
            'attr' => [
                'help' => 'Personnage qui possède cet objet',
            ],
        ])
            ->add('groupe', 'entity', [
                'required' => false,
                'mapped' => false,
                'label' => 'Groupe',
                'class' => \App\Entity\Groupe::class,
                'property' => 'nom',
                'attr' => [
                    'help' => 'Groupe qui possède cet objet',
                ],
            ])
            ->add('lieu', 'entity', [
                'required' => false,
                'mapped' => false,
                'label' => 'Lieu',
                'class' => \App\Entity\Lieu::class,
                'property' => 'label',
                'attr' => [
                    'help' => 'Lieu ou est entreposé cet objet',
                ],
            ])
            ->add('submit', 'submit', [
                'label' => 'Valider',
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\Item::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'item';
    }
}
