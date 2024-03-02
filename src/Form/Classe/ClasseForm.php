<?php

namespace App\Form\Classe;

use App\Entity\Classe;
use App\Repository\CompetenceFamilyRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
        $builder
            ->add('label_masculin', TextType::class, [
                'required' => true,
                'attr' => ['maxlength' => 45],
                'constraints' => [new Length(['max' => 45])],
            ])
            ->add('image_m', TextType::class, [
                'label' => 'Adresse de l\'image utilisée pour représenter cette classe',
                'required' => true,
                'attr' => ['maxlength' => 90],
                'constraints' => [new Length(['max' => 90])],
            ])
            ->add('label_feminin', TextType::class, [
                'required' => true,
                'attr' => ['maxlength' => 45],
                'constraints' => [new Length(['max' => 45])],
            ])
            ->add('image_f', TextType::class, [
                'label' => 'Adresse de l\'image utilisée pour représenter cette classe',
                'required' => true,
                'attr' => ['maxlength' => 90],
                'constraints' => [new Length(['max' => 90])],
            ])
            ->add('creation', ChoiceType::class, [
                'required' => true,
                'expanded' => true,
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'label' => 'Disponible lors de la création d\'un nouveau personnage',
                'help' => 'Choisissez si cette classe sera disponible ou pas lors de la création d\'un nouveau personnage',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'maxlength' => 450,
                ],
                'constraints' => [new Length(['max' => 450])],
            ])
            ->add('competenceFamilyFavorites', EntityType::class, [
                    'label' => "Famille de compétences favorites (n'oubliez pas de cocher aussi la/les compétences acquises à la création)",
                    'required' => false,
                    'choice_label' => 'label',
                    'multiple' => true,
                    'expanded' => true,
                    'mapped' => true,
                    'class' => \App\Entity\CompetenceFamily::class,
                    'query_builder' => static function (CompetenceFamilyRepository $cfr) {
                        return $cfr->createQueryBuilder('cfr')->orderBy('cfr.label', 'ASC');
                    },
                ]
            )
            ->add('competenceFamilyNormales', EntityType::class, [
                    'label' => 'Famille de compétences normales',
                    'required' => false,
                    'choice_label' => 'label',
                    'multiple' => true,
                    'expanded' => true,
                    'mapped' => true,
                    'class' => \App\Entity\CompetenceFamily::class,
                    'query_builder' => static function (CompetenceFamilyRepository $cfr) {
                        return $cfr->createQueryBuilder('cfr')->orderBy('cfr.label', 'ASC');
                    },
                ]
            )
            ->add('competenceFamilyCreations', EntityType::class, [
                    'label' => 'Famille de compétences acquises à la création',
                    'required' => false,
                    'choice_label' => 'label',
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
            'data_class' => Classe::class,
            // TinyMce Hide the text field. It's break the form Submit because autovalidate can't allow it
            // Reason : the user can't fill a hidden field, so it's couldn't be "required"
            'attr' => [
                'novalidate' => 'novalidate',
            ],
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
