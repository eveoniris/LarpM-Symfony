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

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\AnnonceForm
 * 
 * @author kevin
 *
 */
class AnnonceForm extends AbstractType
{
	/**
	 * Construction du formulaire
	 * 
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->add('title','text', array(
					'label' => 'Titre',
					'required' => true,	
				))
				->add('archive','choice', array(
						'required' => true,
						'choices' => array(false => 'Publique', true => 'Dans les archive'),
						'label' => 'Choisissez la visibilité de votre annonce',
				))
				->add('gn', 'entity', array(
						'label' => 'Choisissez le jeu auquel cette annonce fait référence',
						'required' => true,
						'multiple' => false,
						'class' => 'App\Entity\Gn',
						'property' => 'label',
						'empty_data'  => null,
						'placeholder' => 'Aucun',
				))
				->add('text','textarea', array(
					'required' => true,
					'attr' => array(
						'rows' => 9,
						'class' => 'tinymce'),
				));
	}
	
	/**
	 * Définition de la classe d'entité concernée
	 * 
	 * @param OptionsResolverInterface $resolver
	 */
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults(array(
				'class' => 'App\Entity\Annonce',
		));
	}
	
	/**
	 * Nom du formlaire
	 */
	public function getName()
	{
		return 'annonce';
	}
}