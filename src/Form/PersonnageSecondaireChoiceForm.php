<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\PersonnageSecondaireChoiceForm.
 *
 * @author kevin
 */
class PersonnageSecondaireChoiceForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('personnageSecondaire', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'label' => 'Archétype',
            'required' => true,
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
        return 'personnageSecondaireChoice';
    }
}
