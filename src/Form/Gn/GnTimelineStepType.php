<?php

declare(strict_types=1);

namespace App\Form\Gn;

use App\Entity\GnTimelineStep;
use App\Enum\GnTimelineVisibilite;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class GnTimelineStepType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle', TextType::class, ['label' => 'Libellé'])
            ->add('datePrevue', DateTimeType::class, [
                'label' => 'Date prévue',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('visibilite', EnumType::class, [
                'label' => 'Visibilité',
                'class' => GnTimelineVisibilite::class,
                'choice_label' => static fn (GnTimelineVisibilite $v): string => $v->getLabel(),
            ])
            ->add('description', TextareaType::class, ['label' => 'Description', 'required' => false])
            ->add('save', SubmitType::class, ['label' => 'Enregistrer']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => GnTimelineStep::class]);
    }
}
