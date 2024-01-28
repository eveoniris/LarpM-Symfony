<?php

namespace App\Form\Gn;

use App\Entity\Gn;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GnForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'required' => true,
        ])
            ->add('description', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'label' => 'Description du GN',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'row' => 9,
                ],
            ])
            ->add('xpCreation', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                'label' => 'Point d\'expérience à la création d\'un personnage',
                'required' => false,
            ])
            ->add('dateDebut', \Symfony\Component\Form\Extension\Core\Type\DateTimeType::class, [
                'label' => 'Date et heure de début du jeu',
                'required' => false,
            ])
            ->add('dateFin', \Symfony\Component\Form\Extension\Core\Type\DateTimeType::class, [
                'label' => 'Date et heure de fin du jeu',
                'required' => false,
            ])
            ->add('dateInstallationJoueur', \Symfony\Component\Form\Extension\Core\Type\DateTimeType::class, [
                'label' => 'Date et heure du début de l\'accueil des joueurs',
                'required' => false,
            ])
            ->add('dateFinOrga', \Symfony\Component\Form\Extension\Core\Type\DateTimeType::class, [
                'label' => 'Date limite pour libérer le site',
                'required' => false,
            ])
            ->add('adresse', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'label' => 'Adresse du site',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'row' => 9,
                ],
            ])
            ->add('conditionsInscription', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'label' => "Conditions d'inscription",
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 18,
                ],
            ])
            ->add('billetterie', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'label' => 'Code de la billetterie',
                'required' => false,
            ])
            ->add('actif', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
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
            'class' => \App\Entity\Gn::class,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Gn::class,
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
