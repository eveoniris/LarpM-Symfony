<?php


namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Type\ParticipantHasRestaurationType.
 *
 * @author kevin
 */
class ParticipantHasRestaurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('restauration', 'entity', [
            'label' => 'Choisissez le lieu de restauration',
            'required' => true,
            'class' => \App\Entity\Restauration::class,
            'property' => 'label',
        ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver): void
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
