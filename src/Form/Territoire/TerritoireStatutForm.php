<?php


namespace App\Form\Territoire;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Territoire\TerritoireStatutForm.
 *
 * @author kevin
 */
class TerritoireStatutForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('statut', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
            'label' => 'Statut',
            'required' => false,
            'choices' => ['Normal' => 'Normal', 'Instable' => 'Instable'],
        ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\Territoire::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'territoireStatut';
    }
}
