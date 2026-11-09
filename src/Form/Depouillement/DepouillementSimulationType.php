<?php

declare(strict_types=1);

namespace App\Form\Depouillement;

use App\Enum\DepouillementType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class DepouillementSimulationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('type', EnumType::class, [
                'label' => 'Nature de l\'enveloppe',
                'class' => DepouillementType::class,
                'choice_label' => static fn (DepouillementType $type): string => $type->label(),
            ])
            ->add('numero', TextType::class, [
                'label' => 'Numéro du groupe ou du personnage',
                'help' => 'Le numéro inscrit sur l\'enveloppe : numéro du groupe, ou numéro du personnage. Le GN est celui de cette page.',
                'constraints' => [new NotBlank(), new Length(max: 30)],
            ])
            ->add('submit', SubmitType::class, ['label' => $options['avec_reel'] ? 'Démarrer' : 'Démarrer une simulation']);

        if ($options['avec_reel']) {
            $builder->add('reel', CheckboxType::class, [
                'label' => 'Dossier réel (sinon simulation)',
                'help' => 'Un dossier réel est unique pour ce numéro et ce GN, le joueur peut y avoir déjà commencé sa déclaration. Rien n\'est appliqué aux personnages avant la validation.',
                'required' => false,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['avec_reel' => false]);
        $resolver->setAllowedTypes('avec_reel', 'bool');
    }
}
