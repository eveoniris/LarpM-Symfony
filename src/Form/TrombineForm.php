<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\TrombineForm.
 *
 * @author kevin
 */
class TrombineForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('trombine', 'file', [
            'label' => 'Choisissez une photo pour le trombinoscope',
            'required' => true,
        ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        /*$resolver->setDefaults(array(
                'class' => 'App\Entity\Joueur',
        ));*/
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'trombine';
    }
}
