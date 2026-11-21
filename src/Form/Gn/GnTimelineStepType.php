<?php

declare(strict_types=1);

namespace App\Form\Gn;

use App\Entity\GnTimelineStep;
use App\Enum\GnTimelineType;
use App\Enum\GnTimelineVisibilite;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
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
            ->add('type', EnumType::class, [
                'label' => "Type d'étape",
                'class' => GnTimelineType::class,
                'choice_label' => static fn (GnTimelineType $t): string => $t->getLabel(),
            ])
            ->add('xp', IntegerType::class, [
                'label' => 'XP à donner (type « XP de participation »)',
                'required' => false,
                'attr' => ['min' => 1],
            ])
            ->add('auto', CheckboxType::class, [
                'label' => 'Exécuter automatiquement (cron) quand la date est passée. Impossible pour le vieillissement.',
                'required' => false,
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
