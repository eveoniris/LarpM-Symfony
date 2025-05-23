<?php


namespace App\Form;

use App\Entity\Participant;
use App\Entity\PersonnageSecondaire;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipantPersonnageSecondaireForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('personnageSecondaire', EntityType::class, [
            'label' => 'Choisissez un archétype',
            'required' => true,
            'expanded' => true,
            'class' => PersonnageSecondaire::class,
            'choice_label' => 'label']);
    }

    /**
     * Définition de la classe d'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Participant::class,
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
