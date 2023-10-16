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

use Doctrine\ORM\EntityRepository;
use LarpManager\Repository\ClasseRepository;
use LarpManager\Repository\CompetenceRepository;
use LarpManager\Repository\GroupeRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\PersonnageFindForm.
 *
 * @author kevin
 */
class PersonnageFindForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('value', 'text', [
            'required' => false,
            'label' => 'Recherche',
            'attr' => [
                'placeholder' => 'Votre recherche',
                'aria-label' => '...',
            ],
        ])
            ->add('type', 'choice', [
                'required' => false,
                'choices' => [
                    'id' => 'ID',
                    'nom' => 'Nom',
                ],
                'label' => 'Type',
                'attr' => [
                    'aria-label' => '...',
                ],
            ])
            ->add('religion', 'entity', [
                'required' => false,
                'label' => '	Par religion : ',
                'class' => \App\Entity\Religion::class,
                'property' => 'label',
                'query_builder' => static function (EntityRepository $er) {
                    return $er->createQueryBuilder('r')->orderBy('r.label', 'ASC');
                },
            ])
            ->add('competence', 'entity', [
                'required' => false,
                'label' => '	Par compétence : ',
                'class' => \App\Entity\Competence::class,
                'property' => 'label',
                'query_builder' => static function (CompetenceRepository $cr) {
                    return $cr->getQueryBuilderFindAllOrderedByLabel();
                },
            ])
            ->add('classe', 'entity', [
                'required' => false,
                'label' => '	Par classe : ',
                'class' => \App\Entity\Classe::class,
                'property' => 'label',
                'query_builder' => static function (ClasseRepository $er) {
                    return $er->getQueryBuilderFindAllOrderedByLabel();
                },
            ])
            ->add('groupe', 'entity', [
                'required' => false,
                'label' => '	Par groupe : ',
                'class' => \App\Entity\Groupe::class,
                'property' => 'nom',
                'query_builder' => static function (GroupeRepository $gr) {
                    return $gr->createQueryBuilder('gr')->orderBy('gr.nom', 'ASC');
                },
            ]);
    }

    /**
     * Définition de l'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'personnageFind';
    }
}
