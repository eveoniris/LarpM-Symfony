<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
        return 'personnageSecondaireChoice';
    }
}
