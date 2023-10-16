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
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\ClasseForm.
 *
 * @author kevin
 */
class ClasseForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label_masculin', 'text', [
            'required' => true,
        ])
            ->add('image_m', 'text', [
                'label' => 'Adresse de l\'image utilisé pour représenté cette classe',
                'required' => true,
            ])
            ->add('label_feminin', 'text', [
                'required' => true,
            ])
            ->add('image_f', 'text', [
                'label' => 'Adresse de l\'image utilisé pour représenté cette classe',
                'required' => true,
            ])
            ->add('description', 'textarea', [
                    'required' => false, ]
            )
            ->add('competenceFamilyFavorites', 'entity', [
                    'label' => "Famille de compétences favorites (n'oubliez pas de cochez aussi la/les compétences acquises à la création)",
                    'required' => false,
                    'property' => 'label',
                    'multiple' => true,
                    'expanded' => true,
                    'mapped' => true,
                    'class' => \App\Entity\CompetenceFamily::class, ]
            )
            ->add('competenceFamilyNormales', 'entity', [
                    'label' => 'Famille de compétences normales',
                    'required' => false,
                    'property' => 'label',
                    'multiple' => true,
                    'expanded' => true,
                    'mapped' => true,
                    'class' => \App\Entity\CompetenceFamily::class, ]
            )
            ->add('competenceFamilyCreations', 'entity', [
                    'label' => 'Famille de compétences acquises à la création',
                    'required' => false,
                    'property' => 'label',
                    'multiple' => true,
                    'expanded' => true,
                    'mapped' => true,
                    'class' => \App\Entity\CompetenceFamily::class, ]
            );
    }

    /**
     * Définition de l'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\Classe::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'classe';
    }
}
