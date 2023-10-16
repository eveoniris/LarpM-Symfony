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
 * LarpManager\Form\ChronologieForm.
 *
 * @author kevin
 */
class ChronologieForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('territoire', 'entity', [
                'required' => true,
                'label' => 'Territoire',
                'class' => \App\Entity\Territoire::class,
                'property' => 'nom',
            ])
            ->add('description', 'textarea', [
                'required' => true,
                'label' => 'Description',
                'attr' => ['rows' => 9],
            ])
            ->add('year', 'integer', [
                'required' => true,
                'label' => 'Année',
            ])
            ->add('month', 'integer', [
                'required' => false,
                'label' => 'Mois (falcultatif)',
            ])
            ->add('day', 'integer', [
                'required' => false,
                'label' => 'Jour (falcultatif)',
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\Chronologie::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'chronologie';
    }
}
