<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\ParticipantPersonnageSecondaireForm.
 *
 * @author kevin
 */
class ParticipantPersonnageSecondaireForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('personnageSecondaire', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'label' => 'Choisissez un archétype',
            'required' => true,
            'expanded' => true,
            'class' => \App\Entity\PersonnageSecondaire::class,
            'choice_label' => 'label']);
    }

    /**
     * Définition de la classe d'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\Participant::class,
        ]);
    }

    /**
     * Nom du formlaire.
     */
    public function getName(): string
    {
        return 'participantPersonnageSecondaire';
    }
}
