<?php


namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Type\ParticipantHasRestaurationType.
 *
 * @author kevin
 */
class ParticipantHasRestaurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('restauration', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'label' => 'Choisissez le lieu de restauration',
            'required' => true,
            'class' => \App\Entity\Restauration::class,
            'choice_label' => 'label',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\ParticipantHasRestauration::class,
        ]);
    }

    public function getName(): string
    {
        return 'participantHasRestauration';
    }
}
