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

namespace App\Form\Personnage;

use LarpManager\Form\Type\PersonnageRessourceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\PersonnageRessourceForm.
 *
 * @author kevin
 */
class PersonnageRessourceForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('randomCommun', 'integer', [
                'mapped' => false,
                'label' => 'X ressources communes choisies au hasard',
                'required' => false,
                'attr' => [
                    'help' => 'Indiquez combien de ressources COMMUNES il faut ajouter à ce personnage.',
                ],
            ])
            ->add('randomRare', 'integer', [
                'mapped' => false,
                'label' => 'X ressources rares choisies au hasard',
                'required' => false,
                'attr' => [
                    'help' => 'Indiquez combien de ressources RARES il faut ajouter à ce personnage.',
                ],
            ])
            ->add('personnageRessources', 'collection', [
                'label' => 'Ressources',
                'required' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'type' => new PersonnageRessourceType(),
            ])
            ->add('valider', 'submit', ['label' => 'Valider']);
    }

    /**
     * Définition de l'entité conercné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\Personnage::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'personnageRessource';
    }
}
