<?php

/**
 * LarpManager - A Live Action Role Playing Manager
 * Copyright (C) 2016 Kevin Polez
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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use LarpManager\Repository\IngredientRepository;

/**
 * LarpManager\Form\Type\PersonnageIngredientType
 *
 * @author kevin
 *
 */
class PersonnageIngredientType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
			->add('nombre', 'integer', array(
					'label' => 'quantite',
					'required' => true
			))
			->add('ingredient','entity', array(
					'label' => 'Choisissez l\'ingredient',
					'required' => true,
					'class' => 'App\Entity\Ingredient',
					'property' => 'fullLabel',
					'query_builder' => function(IngredientRepository $er) {
						$qb = $er->createQueryBuilder('c');
						$qb->orderBy('c.label', 'ASC')->addOrderBy('c.niveau', 'ASC');
						return $qb;
					}
			));
	}

	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults(array(
				'data_class' => '\App\Entity\PersonnageIngredient',
		));
	}

	public function getName()
	{
		return 'personnageIngredient';
	}
}