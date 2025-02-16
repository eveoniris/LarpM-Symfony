<?php

namespace App\Form\Bonus;

use App\Entity\Bonus;
use App\Enum\BonusApplication;
use App\Enum\BonusPeriode;
use App\Enum\BonusType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BonusForm extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('titre', TextType::class, [
            'required' => true,
            'label' => 'Nom',
        ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description succinte',
            ])
            ->add('type', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'Aucun' => null,
                    ...BonusType::toArray(),
                ],
                'label' => 'Type de bonus',
            ])
            ->add('valeur', IntegerType::class, [
                'required' => false,
                'label' => 'Valeur du bonus',
            ])
            ->add('application', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'Aucun' => null,
                    ...BonusApplication::toArray(),
                ],
                'label' => "Dommaine d'application du bonus",
            ])
            ->add('periode', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'Aucun' => null,
                    ...BonusPeriode::toArray(),
                ],
                'label' => "Dommaine d'application du bonus",
            ]);
    }

    /**
     * Définition de l'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Bonus::class,
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
        return 'bonus';
    }
}
