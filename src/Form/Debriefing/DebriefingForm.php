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

namespace App\Form\Debriefing;

use App\Entity\Gn;
use App\Entity\Groupe;
use App\Entity\User;
use LarpManager\Repository\GroupeRepository;
use LarpManager\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * LarpManager\Form\DebriefingForm.
 *
 * @author kevin
 */
class DebriefingForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('titre', TextType::class, [
            'required' => true,
            'label' => 'Titre',
            'attr' => ['maxlength' => 45],
            'constraints' => [
                new Length(['max' => 45]),
                new NotBlank(),
            ],
        ])
            ->add('text', TextareaType::class, [
                'required' => true,
                'label' => 'Contenu',
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 15,
                ],
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('player', EntityType::class, [
                'required' => false,
                'label' => 'Joueur',
                'class' => User::class,
                'property' => 'Username',
                'placeholder' => 'Choisissez le joueur qui vous a fourni ce debriefing',
                'query_builder' => static function (UserRepository $p) {
                    $qb = $p->createQueryBuilder('p');
                    $qb->orderBy('p.Username', 'ASC');

                    return $qb;
                },
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('groupe', EntityType::class, [
                'required' => true,
                'label' => 'Groupe',
                'class' => Groupe::class,
                'property' => 'nom',
                'query_builder' => static function (GroupeRepository $g) {
                    $qb = $g->createQueryBuilder('g');
                    $qb->orderBy('g.nom', 'ASC');

                    return $qb;
                },
            ])
            ->add('gn', EntityType::class, [
                'required' => true,
                'label' => 'GN',
                'class' => Gn::class,
                'property' => 'label',
                'placeholder' => 'Choisissez le GN auquel est lié ce debriefing',
                'empty_data' => null,
            ])
            ->add('document', FileType::class, [
                'label' => 'Téléversez un document PDF',
                'required' => false,
                'mapped' => false,
                'attr' => ['accept' => '.pdf'], ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\Debriefing::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'debriefing';
    }
}
