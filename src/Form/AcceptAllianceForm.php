<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\AgeForm.
 *
 * @author kevin
 */
class AcceptAllianceForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('messageAllie', 'textarea', [
            'label' => 'Un petit mot pour expliquer votre démarche',
            'required' => true,
            'attr' => [
                'class' => 'tinymce',
                'rows' => 9,
                'help' => 'Ce texte sera transmis au chef de groupe concerné.'],
        ]);
    }

    /**
     * Définition de la classe d'entité concernée.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\GroupeAllie::class,
        ]);
    }

    /**
     * Nom du formlaire.
     */
    public function getName(): string
    {
        return 'acceptAlliance';
    }
}
