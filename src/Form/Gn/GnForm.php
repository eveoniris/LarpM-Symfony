<?php

namespace App\Form\Gn;

use App\Entity\Gn;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class GnForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', 'text', [
            'required' => true,
        ])
            ->add('description', 'textarea', [
                'label' => 'Description du GN',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'row' => 9,
                ],
            ])
            ->add('xpCreation', 'integer', [
                'label' => 'Point d\'expérience à la création d\'un personnage',
                'required' => false,
            ])
            ->add('dateDebut', 'datetime', [
                'label' => 'Date et heure de début du jeu',
                'required' => false,
            ])
            ->add('dateFin', 'datetime', [
                'label' => 'Date et heure de fin du jeu',
                'required' => false,
            ])
            ->add('dateInstallationJoueur', 'datetime', [
                'label' => 'Date et heure du début de l\'accueil des joueurs',
                'required' => false,
            ])
            ->add('dateFinOrga', 'datetime', [
                'label' => 'Date limite pour libérer le site',
                'required' => false,
            ])
            ->add('adresse', 'textarea', [
                'label' => 'Adresse du site',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'row' => 9,
                ],
            ])
            ->add('conditionsInscription', 'textarea', [
                'label' => "Conditions d'inscription",
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 18,
                ],
            ])
            ->add('billetterie', 'textarea', [
                'label' => 'Code de la billetterie',
                'required' => false,
            ])
            ->add('actif', 'choice', [
                'label' => 'GN actif ?',
                'required' => true,
                'choices' => [
                    true => 'Oui',
                    false => 'Non',
                ],
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
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
