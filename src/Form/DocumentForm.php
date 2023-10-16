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

use LarpManager\Repository\LangueRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\DocumentForm.
 *
 * @author kevin
 */
class DocumentForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('code', 'text', [
            'required' => true,
            'attr' => [
                'help' => 'Le code d\'un document permet de l\'identifier rapidement. Il se construit de la manière suivante : L3_DJ_TE_005. L3 correspond à l\'opus de création. DJ correspond à Document en Jeu. TE correspond à TExte. 005 correspond à son numéro (suivez la numérotation des documents déjà créé)',
            ],
        ])
            ->add('titre', 'text', [
                'required' => true,
            ])
            ->add('auteur', 'text', [
                'required' => true,
                'empty_data' => null,
                'attr' => [
                    'help' => 'Indiquez l\'auteur (en jeu) du document. Cet auteur est soit un personnage fictif (p.e. le célébre poète Archibald) ou l\'un des personnage joué par un joueur',
                ],
            ])
            ->add('langues', 'entity', [
                'required' => true,
                'multiple' => true,
                'expanded' => true,
                'label' => 'Langues dans lesquelles le document est rédigé',
                'class' => \App\Entity\Langue::class,
                'query_builder' => static function (LangueRepository $er) {
                    return $er->createQueryBuilder('l')->orderBy('l.label', 'ASC');
                },
                'property' => 'label',
                'attr' => [
                    'help' => 'Vous pouvez choisir une ou plusieurs langues',
                ],
            ])
            ->add('cryptage', 'choice', [
                'required' => true,
                'choices' => [false => 'Non crypté', true => 'Crypté'],
                'label' => 'Indiquez si le document est crypté',
                'attr' => [
                    'help' => 'Un document crypté est rédigé dans la langue indiqué, mais le joueur doit le décrypter de lui-même (p.e rédigé en aquilonien, mais utilisant un code type césar)',
                ],
            ])
            ->add('description', 'textarea', [
                'required' => true,
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 9,
                    'help' => 'Une courte description du document permet d\'éviter de télécharger et d\'ouvrir le document pour comprendre quel est son contenu.',
                ],
            ])
            ->add('statut', 'text', [
                'required' => false,
                'attr' => [
                    'help' => 'Une courte description du document permet d\'éviter de télécharger et d\'ouvrir le document pour comprendre quel est son contenu.',
                ],
            ])
            ->add('impression', 'choice', [
                'required' => false,
                'choices' => [false => 'Non imprimé', true => 'Imprimé'],
                'label' => 'Indiquez si le document a été imprimé',
                'attr' => [
                    'help' => 'Le responsable des documents devra indiqué pour chacun des documents s\'ils ont été imprimés ou pas.',
                ],
            ])
            ->add('document', 'file', [
                'label' => 'Choisissez votre fichier',
                'required' => true,
                'mapped' => false,
                'attr' => [
                    'help' => 'Téléversez le fichier PDF correspondant à votre document.',
                ],
            ]);
    }

    /**
     * Définition de la classe d'entité concernée.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\Document::class,
        ]);
    }

    /**
     * Nom du formlaire.
     */
    public function getName(): string
    {
        return 'document';
    }
}
