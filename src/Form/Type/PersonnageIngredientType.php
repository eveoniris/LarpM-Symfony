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

namespace App\Form\Type;

use LarpManager\Repository\IngredientRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Type\PersonnageIngredientType.
 *
 * @author kevin
 */
class PersonnageIngredientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', 'integer', [
                'label' => 'quantite',
                'required' => true,
            ])
            ->add('ingredient', 'entity', [
                'label' => "Choisissez l'ingredient",
                'required' => true,
                'class' => \App\Entity\Ingredient::class,
                'property' => 'fullLabel',
                'query_builder' => static function (IngredientRepository $er) {
                    $qb = $er->createQueryBuilder('c');
                    $qb->orderBy('c.label', 'ASC')->addOrderBy('c.niveau', 'ASC');

                    return $qb;
                },
            ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\PersonnageIngredient::class,
        ]);
    }

    public function getName(): string
    {
        return 'personnageIngredient';
    }
}
