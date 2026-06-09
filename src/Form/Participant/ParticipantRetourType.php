<?php

declare(strict_types=1);

namespace App\Form\Participant;

use App\Entity\Participant;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<Participant> */
class ParticipantRetourType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['has_alchimie']) {
            $builder->add('carteAlchiDansEnveloppe', CheckboxType::class, [
                'label' => 'Carte alchimiste/herboriste restée dans l\'enveloppe',
                'required' => false,
            ]);
        }

        if ($options['has_previous_gn']) {
            $builder->add('enveloppePrecedentGn', CheckboxType::class, [
                'label' => 'Enveloppe du GN précédent présente',
                'required' => false,
            ]);
        }

        $builder->add('save', SubmitType::class, ['label' => 'Enregistrer le retour']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
            'has_alchimie' => false,
            'has_previous_gn' => false,
        ]);
    }
}
