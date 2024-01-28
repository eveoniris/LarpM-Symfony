<?php


namespace App\Form;

use App\Form\Type\ParticipantHasRestaurationType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\ParticipantRestaurationForm.
 *
 * @author kevin
 */
class ParticipantRestaurationForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('participantHasRestaurations', \Symfony\Component\Form\Extension\Core\Type\CollectionType::class, [
            'label' => 'Lieux de restauration',
            'required' => false,
            'allow_add' => true,
            'allow_delete' => true,
            'by_reference' => false,
            'entry_type' => ParticipantHasRestaurationType::class,
        ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\Participant::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'participantRestauration';
    }
}
