<?php


namespace App\Form;

use App\Entity\EtatCivil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EtatCivilForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nom', TextType::class, [
            'label' => 'Nom civil',
            'required' => true,
            'attr' => [
                'placeholder' => 'Nom civil',
            ],
        ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom civil',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Prénom civil',
                ],
            ])
            ->add('prenom_usage', TextType::class, [
                'label' => "Nom d'usage",
                'required' => false,
                'attr' => [
                    'placeholder' => "Nom d'usage",
                ],
            ])
            ->add('date_naissance', DateType::class, [
                'label' => 'Date de naissance',
                'required' => true,
                'years' => range(1900, 2020),
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Numéro de téléphone',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Numéro de téléphone',
                ],
            ])
            ->add('probleme_medicaux', TextareaType::class, [
                'label' => 'Problèmes médicaux',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Indiquez içi les problèmes médicaux qui vous semblent pertinants',
                ],
            ])
            ->add('personne_a_prevenir', TextType::class, [
                'label' => 'Personne à prévenir en cas d\'urgence',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Nom et prénom',
                ],
            ])
            ->add('tel_pap', TextType::class, [
                'label' => 'Numéro de téléphone de la personne à prévenir',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Numéro de téléphone',
                ],
            ])
            ->add('fedegn', TextType::class, [
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
        return 'etatCivil';
    }
}
