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

namespace App\Form\GroupeGn;

use LarpManager\Repository\ParticipantRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\GroupeGn\GroupeGnOrdreForm.
 *
 * @author Kevin F.
 */
class GroupeGnOrdreForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('initiative', 'integer', [
                'required' => false,
            ])
            ->add('bateaux', 'integer', [
                'required' => false,
            ])
            ->add('agents', 'integer', [
                'required' => false,
            ])
            ->add('sieges', 'integer', [
                'required' => false,
                'label' => 'Armes de Siège',
            ])
            ->add('suzerain', 'entity', [
                'required' => false,
                'class' => \App\Entity\Participant::class,
                'property' => 'personnage.nom',
                'query_builder' => static function (ParticipantRepository $er) use ($options) {
                    $qb = $er->createQueryBuilder('p');
                    $qb->join('p.personnage', 'u');
                    $qb->join('p.groupeGn', 'gg');
                    $qb->orderBy('u.nom', 'ASC');
                    $qb->where('gg.id = :groupeGnId');
                    $qb->setParameter('groupeGnId', $options['groupeGnId']);

                    return $qb;
                },
                'attr' => [
                    'class' => 'selectpicker',
                    'data-live-search' => 'true',
                    'placeholder' => 'Responsable',
                ],
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\GroupeGn::class,
            'groupeGnId' => false,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'groupeGnOrdre';
    }
}
