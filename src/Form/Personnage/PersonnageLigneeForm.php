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

namespace App\Form\Personnage;

use LarpManager\Repository\LigneesRepository;
use LarpManager\Repository\PersonnageRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\PersonnageLigneeForm.
 *
 * @author Kevin F.
 */
class PersonnageLigneeForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('parent1', 'entity', [
            'label' => 'Choisissez le Parent 1 du personnage',
            'expanded' => false,
            'required' => false,
            'class' => \App\Entity\Personnage::class,
            'choice_label' => static function ($personnage) {
                return $personnage->getIdentity();
            },
            'query_builder' => static function (PersonnageRepository $pr) {
                return $pr->createQueryBuilder('p')->orderBy('p.nom', 'ASC');
            },
        ])
            ->add('parent2', 'entity', [
                'label' => 'Choisissez le Parent 2 du personnage',
                'expanded' => false,
                'required' => false,
                'empty_data' => null,
                'class' => \App\Entity\Personnage::class,
                'choice_label' => static function ($personnage) {
                    return $personnage->getIdentity();
                },
                'query_builder' => static function (PersonnageRepository $pr) {
                    return $pr->createQueryBuilder('p')->orderBy('p.nom', 'ASC');
                },
            ])
            ->add('lignee', 'entity', [
                'label' => 'Choisissez la lignée de votre personnage ',
                'expanded' => false,
                'required' => false,
                'empty_data' => null,
                'class' => \App\Entity\Lignee::class,
                'query_builder' => static function (LigneesRepository $pr) {
                    return $pr->createQueryBuilder('p')->orderBy('p.nom', 'ASC');
                },
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'personnageLignee';
    }
}
