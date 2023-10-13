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

namespace App\Form\Groupe;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\ORM\EntityRepository;

use LarpManager\Repository\DocumentRepository;


/**
 * LarpManager\Form\GroupeDocumentForm
 *
 * @author kevin
 *
 */
class GroupeDocumentForm extends AbstractType
{
	/**
	 * Construction du formulaire
	 * 
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->add('documents','entity', array(
					'label' => "Choisissez les documents possédé par le groupe en début de jeu",
					'multiple' => true,
					'expanded' => true,
					'required' => false,
					'class' => 'App\Entity\Document',
					'property' => 'identity',
					'query_builder' => function(DocumentRepository $er) {
						return $er->createQueryBuilder('d')->orderBy('d.code', 'ASC');
					},
				));
	}
		
	/**
	 * Définition de l'entité conercné
	 *
	 * @param OptionsResolverInterface $resolver
	 */
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults(array(
				'data_class' => '\App\Entity\Groupe',
		));
	}
	
	/**
	 * Nom du formulaire 
	 * @return string
	 */
	public function getName()
	{
		return 'groupeDocument';
	}
}