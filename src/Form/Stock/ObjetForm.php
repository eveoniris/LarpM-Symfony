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

namespace App\Form\Stock;

use LarpManager\Form\Type\ObjetCaracType;
use LarpManager\Form\Type\PhotoType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Type\ObjetType.
 *
 * @author kevin
 */
class ObjetForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nom', 'text', ['required' => true])
            ->add('numero', 'text', ['required' => true])
            ->add('description', 'textarea', ['required' => false])
            ->add('photo', new PhotoType(), ['required' => false])
            ->add('proprietaire', 'entity', ['required' => false, 'class' => \App\Entity\Proprietaire::class, 'property' => 'nom'])
            ->add('responsable', 'entity', ['required' => false, 'class' => \App\Entity\User::class, 'property' => 'name'])
            ->add('rangement', 'entity', ['required' => false, 'class' => \App\Entity\Rangement::class, 'property' => 'adresse'])
            ->add('etat', 'entity', ['required' => false, 'class' => \App\Entity\Etat::class, 'property' => 'label'])
            ->add('tags', 'entity', ['required' => false, 'class' => \App\Entity\Tag::class, 'property' => 'nom', 'multiple' => true])
            ->add('objetCarac', new ObjetCaracType(), ['required' => false])
            ->add('cout', 'integer', ['required' => false])
            ->add('nombre', 'integer', ['required' => false])
            ->add('budget', 'integer', ['required' => false])
            ->add('investissement', 'choice', ['choices' => ['true' => 'ré-utilisable', 'false' => 'usage unique']]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\Objet::class,
            'cascade_validation' => true,
        ]);
    }

    public function getName(): string
    {
        return 'objet';
    }
}
