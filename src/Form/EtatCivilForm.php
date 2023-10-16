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
 * LarpManager\Form\JoueurForm.
 *
 * @author kevin
 */
class EtatCivilForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nom', 'text', [
            'label' => 'Nom civil',
            'required' => true,
            'attr' => [
                'placeholder' => 'Nom civil',
            ],
        ])
            ->add('prenom', 'text', [
                'label' => 'Prénom civil',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Prénom civil',
                ],
            ])
            ->add('prenom_usage', 'text', [
                'label' => "Nom d'usage",
                'required' => false,
                'attr' => [
                    'placeholder' => "Nom d'usage",
                ],
            ])
            ->add('date_naissance', 'date', [
                'label' => 'Date de naissance',
                'required' => true,
                'years' => range(1900, 2020),
            ])
            ->add('telephone', 'text', [
                'label' => 'Numéro de téléphone',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Numéro de téléphone',
                ],
            ])
            ->add('probleme_medicaux', 'textarea', [
                'label' => 'Problèmes médicaux',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Indiquez içi les problèmes médicaux qui vous semblent pertinants',
                ],
            ])
            ->add('personne_a_prevenir', 'text', [
                'label' => 'Personne à prévenir en cas d\'urgence',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Nom et prénom',
                ],
            ])
            ->add('tel_pap', 'text', [
                'label' => 'Numéro de téléphone de la personne à prévenir',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Numéro de téléphone',
                ],
            ])
            ->add('fedegn', 'text', [
                'label' => 'Numéro de carte FédéGN',
                'required' => false,
                'attr' => [
                    'placeholder' => 'votre numéro de carte GN ou GN+',
                ],
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\EtatCivil::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'etatCivil';
    }
}
