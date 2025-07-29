<?php

namespace App\Form;

use App\Entity\EtatCivil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JoueurForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nom', TextType::class, [
            'label' => 'Nom civil',
            'required' => true,
        ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom civil',
                'required' => true,
            ])
            ->add('prenom_usage', TextType::class, [
                'label' => "Nom d'usage",
                'required' => false,
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Numéro de téléphone',
                'required' => true,
            ])
            ->add('probleme_medicaux', TextareaType::class, [
                'label' => 'Eventuel problèmes médicaux',
                'required' => true,
            ])
            ->add('personne_a_prevenir', TextType::class, [
                'label' => 'Personne à prévenir en cas de problème',
                'required' => true,
            ])
            ->add('tel_pap', TextType::class, [
                'label' => 'Numéro de téléphone de la personne à prévenir',
                'required' => true,
            ])
            ->add('fedegn', TextType::class, [
                'label' => 'Numéro d’adhérent FédéGN',
                'required' => true,
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => EtatCivil::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'EtatCivil';
    }
}
