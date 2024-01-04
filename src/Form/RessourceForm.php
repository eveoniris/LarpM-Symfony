<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\RessourceForm.
 *
 * @author kevin
 */
class RessourceForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', 'text', [
            'required' => true,
        ])
            ->add('rarete', 'entity', [
                'label' => 'Rareté',
                'required' => true,
                'property' => 'label',
                'multiple' => false,
                'mapped' => true,
                'class' => \App\Entity\Rarete::class,
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data' => \App\Entity\Ressource::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'ressource';
    }
}
