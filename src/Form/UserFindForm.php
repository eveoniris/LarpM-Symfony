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

namespace App\Form;

use App\Form\Entity\UserSearch;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * LarpManager\Form\UserFindForm.
 *
 * @author kevin
 */
class UserFindForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('value', TextType::class, [
            'required' => true,
            'label' => 'Recherche',
        ])
            ->add('type', ChoiceType::class, [
                'required' => true,
                'choices' => [
                   // TODO etatCivil.name? 'name' ,
                    '*',
                    'username',
                    'roles',
                    'email',
                    'id',
                ],
                'choice_label' => static fn ($value) => match ($value) {
                    '*' => 'Tout',
                    'username' => 'Pseudo',
                    'roles' => 'Roles',
                    'name' => 'Nom',
                    'email' => 'Email',
                    'id' => 'ID',
                },
                'label' => 'Type',
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'UserFind';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $constraints = [
            'value' => new NotBlank(),
            'type' => [
                new NotBlank(),
            ],
        ];

        $resolver->setDefaults([
            'data_class' => UserSearch::class,
     //       'constraints' => $constraints,
        ]);
    }
}
