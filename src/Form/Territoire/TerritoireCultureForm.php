<?php


namespace App\Form\Territoire;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Territoire\TerritoireCultureForm.
 *
 * @author kevin
 */
class TerritoireCultureForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('culture', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'required' => false,
            'label' => 'Culture',
            'class' => \App\Entity\Culture::class,
            'multiple' => false,
            'expanded' => true,
            'mapped' => true,
            'choice_label' => 'label',
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
        return 'territoireCulture';
    }
}
