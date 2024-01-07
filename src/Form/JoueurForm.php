<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\JoueurForm.
 *
 * @author kevin
 */
class JoueurForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nom', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'label' => 'Nom civil',
            'required' => true,
        ])
            ->add('prenom', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => 'Prénom civil',
                'required' => true,
            ])
            ->add('prenom_usage', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => "Nom d'usage",
                'required' => false,
            ])
            ->add('telephone', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => 'Numéro de téléphone',
                'required' => true,
            ])
            ->add('probleme_medicaux', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'label' => 'Eventuel problèmes médicaux',
                'required' => true,
            ])
            ->add('personne_a_prevenir', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => 'Personne à prévenir en cas de problème',
                'required' => true,
            ])
            ->add('tel_pap', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => 'Numéro de téléphone de la personne à prévenir',
                'required' => true,
            ])
            ->add('fedegn', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => 'Numéro d’adhérent FédéGN',
                'required' => true,
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'class' => 'App\Entity\Joueur',
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'joueur';
    }
}
