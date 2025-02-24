<?php

namespace App\Form\Bonus;

use App\Entity\Bonus;
use App\Enum\BonusApplication;
use App\Enum\BonusPeriode;
use App\Enum\BonusType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
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
                'help' => "Pour un bonus d'origine choisir NATIVE: Le bonus ne sera donné que fonction du 1er groupe de participation. Si le personage est natif du pays du groupe",
            ])
            ->add('json_data', TextareaType::class, [
                'required' => false,
                'label' => 'Données fonctionnel (pour un dev)',
            ])->get('json_data')
            ->addModelTransformer(
                new CallbackTransformer(
                    static fn ($data) => json_encode($data, JSON_THROW_ON_ERROR),
                    static fn ($data) => json_decode($data, true, 512, JSON_THROW_ON_ERROR)
                )
            );
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
