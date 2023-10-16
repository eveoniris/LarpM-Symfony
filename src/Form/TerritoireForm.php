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
 * LarpManager\Form\TerritoireForm.
 *
 * @author kevin
 */
class TerritoireForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nom', 'text', [
            'label' => 'Nom',
            'required' => true,
        ])
            ->add('appelation', 'entity', [
                'label' => "Choisissez l'appelation de ce territoire",
                'required' => true,
                'class' => \App\Entity\Appelation::class,
                'multiple' => false,
                'mapped' => true,
                'property' => 'label',
            ])
            ->add('description', 'textarea', [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 10],
            ])
            ->add('description_secrete', 'textarea', [
                'label' => 'Description connue des habitants',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 10],
            ])
            ->add('statut', 'choice', [
                'label' => 'Statut',
                'required' => false,
                'choices' => ['Normal' => 'Normal', 'Instable' => 'Instable'],
            ])
            ->add('geojson', 'textarea', [
                'label' => 'GeoJSON',
                'required' => false,
                'attr' => ['rows' => 10],
            ])
            ->add('capitale', 'text', [
                'label' => 'Capitale',
                'required' => false,
            ])
            ->add('politique', 'text', [
                'label' => 'Système politique',
                'required' => false,
            ])
            ->add('dirigeant', 'text', [
                'label' => 'Dirigeant',
                'required' => false,
            ])
            ->add('population', 'text', [
                'label' => 'Population',
                'required' => false,
            ])
            ->add('symbole', 'text', [
                'label' => 'Symbole',
                'required' => false,
            ])
            ->add('tech_level', 'text', [
                'label' => 'Niveau technologique',
                'required' => false,
            ])
            ->add('type_racial', 'textarea', [
                'label' => 'Type racial',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('inspiration', 'textarea', [
                'label' => 'Inspiration',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('armes_predilection', 'textarea', [
                'label' => 'Armes de prédilection',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('vetements', 'textarea', [
                'label' => 'Vetements',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('noms_masculin', 'textarea', [
                'label' => 'Noms masculins',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('noms_feminin', 'textarea', [
                'label' => 'Noms féminins',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('frontieres', 'textarea', [
                'label' => 'Frontières',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('importations', 'entity', [
                'required' => false,
                'label' => 'Importations',
                'class' => \App\Entity\Ressource::class,
                'multiple' => true,
                'expanded' => true,
                'mapped' => true,
                'property' => 'label',
            ])
            ->add('exportations', 'entity', [
                'required' => false,
                'label' => 'Exportations',
                'class' => \App\Entity\Ressource::class,
                'multiple' => true,
                'expanded' => true,
                'mapped' => true,
                'property' => 'label',
            ])
            ->add('languePrincipale', 'entity', [
                'required' => false,
                'label' => 'Langue principale',
                'class' => \App\Entity\Langue::class,
                'multiple' => false,
                'mapped' => true,
                'property' => 'label',
            ])
            ->add('langues', 'entity', [
                'required' => false,
                'label' => 'Langues parlées (selectionnez aussi la langue principale)',
                'class' => \App\Entity\Langue::class,
                'multiple' => true,
                'expanded' => true,
                'mapped' => true,
                'property' => 'label',
            ])
            ->add('religionPrincipale', 'entity', [
                'required' => false,
                'label' => 'Religion dominante',
                'class' => \App\Entity\Religion::class,
                'multiple' => false,
                'mapped' => true,
                'property' => 'label',
            ])
            ->add('religions', 'entity', [
                'required' => false,
                'label' => 'Religions secondaires',
                'class' => \App\Entity\Religion::class,
                'multiple' => true,
                'expanded' => true,
                'mapped' => true,
                'property' => 'label',
            ])
            ->add('territoire', 'entity', [
                'required' => false,
                'label' => 'Ce territoire dépend de ',
                'class' => \App\Entity\Territoire::class,
                'property' => 'nom',
                'empty_value' => 'Aucun, territoire indépendant',
                'empty_data' => null,
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\Territoire::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'territoire';
    }
}
