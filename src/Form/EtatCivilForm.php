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
class EtatCivilForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nom', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'label' => 'Nom civil',
            'required' => true,
            'attr' => [
                'placeholder' => 'Nom civil',
            ],
        ])
            ->add('prenom', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => 'Prénom civil',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Prénom civil',
                ],
            ])
            ->add('prenom_usage', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => "Nom d'usage",
                'required' => false,
                'attr' => [
                    'placeholder' => "Nom d'usage",
                ],
            ])
            ->add('date_naissance', 'date', [
                'label' => 'Date de naissance',
                'required' => true,
                'years' => range(1900, 2020),
            ])
            ->add('telephone', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => 'Numéro de téléphone',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Numéro de téléphone',
                ],
            ])
            ->add('probleme_medicaux', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'label' => 'Problèmes médicaux',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Indiquez içi les problèmes médicaux qui vous semblent pertinants',
                ],
            ])
            ->add('personne_a_prevenir', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => 'Personne à prévenir en cas d\'urgence',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Nom et prénom',
                ],
            ])
            ->add('tel_pap', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => 'Numéro de téléphone de la personne à prévenir',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Numéro de téléphone',
                ],
            ])
            ->add('fedegn', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => 'Numéro de carte FédéGN',
                'required' => false,
                'attr' => [
                    'placeholder' => 'votre numéro de carte GN ou GN+',
                ],
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\EtatCivil::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'etatCivil';
    }
}
