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

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\PersonnageUpdateLangueForm.
 *
 * @author kevin
 */
class PersonnageUpdateLangueForm extends AbstractType
{
    /**
     * Construction du formulaire
     * Seul les éléments ne dépendant pas des points d'expérience sont modifiables.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('langues', 'entity', [
            'required' => true,
            'multiple' => true,
            'expanded' => true,
            'class' => \App\Entity\Langue::class,
            'choice_label' => 'label',
            'label' => 'Choisissez les langues du personnage',
            'mapped' => false,
            'query_builder' => static function (EntityRepository $repository) {
                return $repository->createQueryBuilder('l')->addOrderBy('l.secret', 'ASC')->addOrderBy('l.diffusion', 'DESC')->addOrderBy('l.label', 'ASC');
            },
        ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'personnageUpdateLangue';
    }
}
