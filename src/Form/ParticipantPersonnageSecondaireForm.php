<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
            'property' => 'label']);
    }

    /**
     * Définition de la classe d'entité concernée.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
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
