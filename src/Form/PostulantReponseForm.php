<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\PostulantReponseForm.
 *
 * @author kevin
 */
class PostulantReponseForm extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('explanation', 'textarea', [
            'label' => 'Votre réponse',
            'required' => true,
            'attr' => [
                'rows' => 9,
                'help' => "un petit mot de bienvenue, ou d'explication de votre refus"],
        ]);
    }

    /**
     * Définition de l'entité conercné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'postulantReponse';
    }
}
