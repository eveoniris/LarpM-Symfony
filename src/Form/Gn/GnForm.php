<?php

namespace App\Form\Gn;

use App\Entity\Gn;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GnForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', TextType::class, [
            'required' => true,
        ])
            ->add('description', TextareaType::class, [
                'label' => 'Description du GN',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'row' => 9,
                ],
            ])
            ->add('xpCreation', IntegerType::class, [
                'label' => 'Point d\'expérience à la création d\'un personnage',
                'required' => false,
            ])
            ->add('dateDebut', DateTimeType::class, [
                'label' => 'Date et heure de début du jeu',
                'required' => false,
            ])
            ->add('dateFin', DateTimeType::class, [
                'label' => 'Date et heure de fin du jeu',
                'required' => false,
            ])
            ->add('dateInstallationJoueur', DateTimeType::class, [
                'label' => 'Date et heure du début de l\'accueil des joueurs',
                'required' => false,
            ])
            ->add('dateFinOrga', DateTimeType::class, [
                'label' => 'Date limite pour libérer le site',
                'required' => false,
            ])
            ->add('adresse', TextareaType::class, [
                'label' => 'Adresse du site',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'row' => 9,
                ],
            ])
            ->add('conditionsInscription', TextareaType::class, [
                'label' => "Conditions d'inscription",
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 18,
                ],
            ])
            ->add('billetterie', TextareaType::class, [
                'label' => 'Code de la billetterie',
                'required' => false,
            ])
            ->add('actif', ChoiceType::class, [
                'label' => 'GN actif ?',
                'required' => true,
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Gn::class,
            // TinyMce Hide the text field. It's break the form Submit because autovalidate can't allow it
            // Reason : the user can't fill a hidden field, so it's couldn't be "required"
            'attr' => [
                'novalidate' => 'novalidate',
            ],
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'gn';
    }
}
