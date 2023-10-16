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

use Doctrine\ORM\EntityRepository;
use LarpManager\Repository\ClasseRepository;
use LarpManager\Repository\TerritoireRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Personnage\PersonnageForm.
 *
 * @author kevin
 */
class PersonnageForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nom', 'text', [
            'required' => true,
            'label' => '',
        ])
            ->add('surnom', 'text', [
                'required' => false,
                'label' => '',
            ])
            ->add('age', 'entity', [
                'required' => true,
                'label' => '',
                'class' => \App\Entity\Age::class,
                'property' => 'label',
                'query_builder' => static function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('a');
                    $qb->andWhere('a.enableCreation = true');

                    return $qb;
                },
            ])
            ->add('genre', 'entity', [
                'required' => true,
                'label' => '',
                'class' => \App\Entity\Genre::class,
                'property' => 'label',
            ])
            ->add('classe', 'entity', [
                'required' => true,
                'label' => 'Votre classe',
                'class' => \App\Entity\Classe::class,
                'property' => 'label',
                'query_builder' => static function (ClasseRepository $er) {
                    $qb = $er->createQueryBuilder('c');
                    $qb->where('c.creation is true');
                    $qb->orderBy('c.label_masculin', 'ASC');

                    return $qb;
                },
            ])
            ->add('territoire', 'entity', [
                'required' => true,
                'label' => 'Votre origine',
                'class' => \App\Entity\Territoire::class,
                'property' => 'nom',
                'query_builder' => static function (TerritoireRepository $er) {
                    $qb = $er->createQueryBuilder('t');
                    $qb->andWhere('t.territoire IS NULL');
                    $qb->orderBy('t.nom', 'ASC');

                    return $qb;
                },
            ])
            ->add('intrigue', 'choice', [
                'required' => true,
                'choices' => [true => 'Oui', false => 'Non'],
                'label' => 'Participer aux intrigues',
            ])
            ->add('sensible', 'choice', [
                'required' => true,
                'choices' => [false => 'Non', true => 'Oui'],
                'label' => 'Personnage sensible',
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\Personnage::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'personnage';
    }
}
