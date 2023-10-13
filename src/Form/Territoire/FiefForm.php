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

namespace App\Form\Territoire;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\GroupFindForm
 *
 * @author kevin
 *
 */
class FiefForm extends AbstractType
{
    /**
     * Construction du formulaire
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('value', 'text', array(
                'required' => false,
                'attr' => array(
                    'placeholder' => 'Votre recherche',
                )
            ))
            ->add('type', 'choice', array(
                'required' => false,
                'choices' => array(
                    'idFief' => 'Id du fief',
                    'nomFief' => 'Nom du fief',
                )
            ))
            ->add('pays', 'entity', array(
                'required' => false,
                'label' => 'Par pays',
                'class' => 'App\Entity\Territoire',
                'choices' => $options['listePays'],
            ))
            ->add('province', 'entity', array(
                'required' => false,
                'label' => 'Par province',
                'class' => 'App\Entity\Territoire',
                'choices' => $options['listeProvinces'],
            ))
            ->add('groupe', 'entity', array(
                'required' => false,
                'label' => 'Par groupe',
                'class' => 'App\Entity\Groupe',
                'choices' => $options['listeGroupes']
            ));
    }

    /**
     * Définition de l'entité concernée
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'listeGroupes'=>'',
                'listePays'=>'',
                'listeProvinces'=>'',
            )
        );
    }

    /**
     * Nom du formulaire
     */
    public function getName()
    {
        return 'fief';
    }
}