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

namespace App\Form\Classe;

use App\Entity\Classe;
use LarpManager\Repository\CompetenceFamilyRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * LarpManager\Form\Classe\ClasseForm.
 *
 * @author kevin
 */
class ClasseForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label_masculin', 'text', [
            'required' => true,
            'attr' => ['maxlength' => 45],
            'constraints' => [new Length(['max' => 45])],
        ])
            ->add('image_m', 'text', [
                'label' => 'Adresse de l\'image utilisé pour représenté cette classe',
                'required' => true,
                'attr' => ['maxlength' => 90],
                'constraints' => [new Length(['max' => 90])],
            ])
            ->add('label_feminin', 'text', [
                'required' => true,
                'attr' => ['maxlength' => 45],
                'constraints' => [new Length(['max' => 45])],
            ])
            ->add('image_f', 'text', [
                'label' => 'Adresse de l\'image utilisé pour représenté cette classe',
                'required' => true,
                'attr' => ['maxlength' => 90],
                'constraints' => [new Length(['max' => 90])],
            ])
            ->add('creation', 'choice', [
                'required' => true,
                'expanded' => true,
                'choices' => [
                    true => 'Oui',
                    false => 'Non',
                ],
                'label' => 'Disponible lors de la création d\'un nouveau personnage',
                'attr' => [
                    'help' => 'Choisissez si cette classe sera disponible ou pas lors de la création d\'un nouveau personnage',
                ],
            ])
            ->add('description', 'textarea', [
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'maxlength' => 450,
                ],
                'constraints' => [new Length(['max' => 450])],
            ])
            ->add('competenceFamilyFavorites', 'entity', [
                    'label' => "Famille de compétences favorites (n'oubliez pas de cocher aussi la/les compétences acquises à la création)",
                    'required' => false,
                    'property' => 'label',
                    'multiple' => true,
                    'expanded' => true,
                    'mapped' => true,
                    'class' => \App\Entity\CompetenceFamily::class,
                    'query_builder' => static function (CompetenceFamilyRepository $cfr) {
                        return $cfr->createQueryBuilder('cfr')->orderBy('cfr.label', 'ASC');
                    },
                ]
            )
            ->add('competenceFamilyNormales', 'entity', [
                    'label' => 'Famille de compétences normales',
                    'required' => false,
                    'property' => 'label',
                    'multiple' => true,
                    'expanded' => true,
                    'mapped' => true,
                    'class' => \App\Entity\CompetenceFamily::class,
                    'query_builder' => static function (CompetenceFamilyRepository $cfr) {
                        return $cfr->createQueryBuilder('cfr')->orderBy('cfr.label', 'ASC');
                    },
                ]
            )
            ->add('competenceFamilyCreations', 'entity', [
                    'label' => 'Famille de compétences acquises à la création',
                    'required' => false,
                    'property' => 'label',
                    'multiple' => true,
                    'expanded' => true,
                    'mapped' => true,
                    'class' => \App\Entity\CompetenceFamily::class,
                    'query_builder' => static function (CompetenceFamilyRepository $cfr) {
                        return $cfr->createQueryBuilder('cfr')->orderBy('cfr.label', 'ASC');
                    },
                ]
            );
    }

    /**
     * Définition de l'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\Classe::class,
            'constraints' => [
                new Callback(static function (Classe $classe, ExecutionContextInterface $context): void {
                    $competenceFamilyLabelsInCommons = $classe->getCompetenceFamilyLabelsInCommons();
                    if ([] !== $competenceFamilyLabelsInCommons) {
                        $context->buildViolation(
                            sprintf(
                                '%s : ne %s être que favorite ou normal, mais pas les deux en même temps',
                                implode(', ', $competenceFamilyLabelsInCommons),
                                \count($competenceFamilyLabelsInCommons) > 1 ? 'peuvent' : 'peut'
                            )
                        )
                            ->addViolation();
                    }

                    /* A activer si on souhaite imposer qu'une compétence offerte à la création ce doit d'être coché comme favorite
                    $competenceFamilyLabelsIncreationNotInFavorites = $classe->getCompetenceFamilyCreationLabelsInNotInFavorites();
                    if (!empty($competenceFamilyLabelsIncreationNotInFavorites)) {
                        $context->buildViolation(
                            sprintf(
                                "%s : ne %s être offerte en création sans être marqué en favoris'",
                                implode(', ', $competenceFamilyLabelsIncreationNotInFavorites),
                                \count($competenceFamilyLabelsIncreationNotInFavorites) > 1 ? 'peuvent' : 'peut'
                            )
                        )
                            ->addViolation();
                    }
                    */
                }),
            ],
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'classe';
    }
}
