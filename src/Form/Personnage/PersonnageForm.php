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

namespace App\Form\Personnage;

use LarpManager\Repository\ClasseRepository;
use LarpManager\Repository\TerritoireRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

/**
 * LarpManager\Form\Personnage\PersonnageForm
 *
 * @author kevin
 *
 */
class PersonnageForm extends AbstractType
{
	/**
	 * Construction du formulaire
	 * 
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->add('nom','text', array(
					'required' => true,
					'label' => '' 
				))
				->add('surnom','text', array(
						'required' => false,
						'label' => ''
				))
				->add('age','entity', array(
						'required' => true,
						'label' => '',
						'class' => 'App\Entity\Age',
						'property' => 'label',
						'query_builder' => function(EntityRepository $er) {
							$qb = $er->createQueryBuilder('a');
							$qb->andWhere('a.enableCreation = true');
							return $qb;
						}
				))
				->add('genre','entity', array(
						'required' => true,
						'label' => '',
						'class' => 'App\Entity\Genre',
						'property' => 'label',
				))
				->add('classe', 'entity', array(
						'required' => true,
						'label' => 'Votre classe',
						'class' => 'App\Entity\Classe',
						'property' => 'label',
						'query_builder' => function(ClasseRepository $er) {
							$qb = $er->createQueryBuilder('c');
							$qb->where('c.creation is true');
							$qb->orderBy('c.label_masculin', 'ASC');
							return $qb;
						}
				))
				->add('territoire','entity', array(
						'required' => true,
						'label' => 'Votre origine',
						'class' => 'App\Entity\Territoire',
						'property' => 'nom',
						'query_builder' => function(TerritoireRepository $er) {
							$qb = $er->createQueryBuilder('t');
							$qb->andWhere('t.territoire IS NULL');
							$qb->orderBy('t.nom', 'ASC');
							return $qb;
						}
				))
				->add('intrigue','choice', array(
						'required' => true,
						'choices' => array(true => 'Oui', false => 'Non'),
						'label' => 'Participer aux intrigues'
				))
				->add('sensible','choice', array(
						'required' => true,
						'choices' => array(false => 'Non', true => 'Oui'),
						'label' => 'Personnage sensible'
				));
	}
	
	/**
	 * Définition de l'entité concerné
	 * 
	 * @param OptionsResolver $resolver
	 */
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
				'data_class' => 'App\Entity\Personnage',
		));
	}
	
	/**
	 * Nom du formulaire
	 */
	public function getName()
	{
		return 'personnage';
	}
}