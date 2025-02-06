<?php


namespace App\Form\Type;

use App\Entity\ParticipantHasRestauration;
use App\Entity\Restauration;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipantHasRestaurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('restauration', EntityType::class, [
            'label' => 'Choisissez le lieu de restauration',
            'required' => true,
            'class' => Restauration::class,
            'choice_label' => 'label',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ParticipantHasRestauration::class,
        ]);
    }

    public function getName(): string
    {
        return 'participantHasRestauration';
    }
}
