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
 * LarpManager\Form\NewMessageForm
 * 
 * @author kevin
 *
 */
class NewMessageForm extends AbstractType
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
					'required' => true,	
					'label' => 'Titre',
					'attr' => array(
						'placeholder' => 'Nouveau message',
					),
				))
				->add('UserRelatedByDestinataire','entity', array(
					'required' => true,
					'label' => 'Destinataire',
					'class' => 'App\Entity\User',
					'property' => 'UserName',
					'attr' => array(
						'class'	=> 'selectpicker',
						'data-live-search' => "true",
						'placeholder' => 'Destinataire',
					),
					'query_builder' => function($er) {
						$qb = $er->createQueryBuilder('u');
						$qb->orderBy('u.Username', 'ASC');
						return $qb;
					},
				))
				->add('text','textarea', array(
					'required' => true,
					'label' => 'Message',
					'attr' => array(
							'rows' => 9,
							'class' => 'tinymce'
					),
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
				'class' => 'App\Entity\Message',
		));
	}
	
	/**
	 * Nom du formlaire
	 */
	public function getName()
	{
		return 'newMessage';
	}
}