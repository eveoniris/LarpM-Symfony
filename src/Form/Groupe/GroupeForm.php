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

namespace App\Form\Groupe;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Groupe\GroupeForm.
 *
 * @author kevin
 */
class GroupeForm extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nom', 'text')
            ->add('numero', 'integer', [
                'required' => true,
            ])
            ->add('pj', 'choice', [
                'label' => 'Type de groupe',
                'required' => true,
                'choices' => [
                    true => 'Groupe composé de PJs',
                    false => 'Groupe composé PNJs',
                ],
                'expanded' => true,
            ])
            ->add('description', 'textarea', [
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'row' => 9,
                ],
            ])
            ->add('scenariste', 'entity', [
                'label' => 'Scénariste',
                'required' => false,
                'class' => \App\Entity\User::class,
                'property' => 'name',
                'query_builder' => static function (EntityRepository $er) {
                    $qb = $er->createQueryBuilder('u');
                    $qb->where($qb->expr()->orX(
                        $qb->expr()->like('u.rights', $qb->expr()->literal('%ROLE_SCENARISTE%')),
                        $qb->expr()->like('u.rights', $qb->expr()->literal('%ROLE_ADMIN%'))));

                    return $qb;
                },
            ]);
    }

    /**
     * Définition de l'entité conercné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\Groupe::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'groupe';
    }
}
