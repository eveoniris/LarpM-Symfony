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
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\DocumentFindForm
 *
 * @author Gectou4
 *
 */
class DocumentFindForm extends AbstractType
{
	/**
	 * Construction du formulaire
	 * 
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder->add('value',\Symfony\Component\Form\Extension\Core\Type\TextType::class, array(
					'required' => true,	
					'label' => 'Recherche',
				))
				->add('type',\Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, array(
					'required' => true,
					'choices' => array(
						'Titre' => 'titre',
                        'Code' => 'code',
                        'Créateur' => 'auteur',
                        'Description' => 'description',
						'ID' => 'id',
					),
					'label' => 'Type',
				));
	}
	
	/**
	 * Définition de l'entité concernée
	 * 
	 * @param OptionsResolverInterface $resolver
	 */
	public function configureOptions(OptionsResolver $resolver): void
	{
	}
	
	/**
	 * Nom du formulaire
	 */
	public function getName(): string
	{
		return 'documentFind';
	}
}