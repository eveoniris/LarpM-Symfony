<?php

declare(strict_types=1);

namespace App\Form\Depouillement;

use App\Enum\DepouillementType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
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
                'label' => 'Numéro de l\'enveloppe (joueur ou groupe)',
                'constraints' => [new NotBlank(), new Length(max: 30)],
            ])
            ->add('submit', SubmitType::class, ['label' => 'Démarrer une simulation']);
    }
}
