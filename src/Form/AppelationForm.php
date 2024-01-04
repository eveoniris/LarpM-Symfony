<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\AppelationForm.
 *
 * @author kevin
 */
class AppelationForm extends AbstractType
{
    /**
     * Construction du formualire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', 'text', [
            'label' => 'Label',
            'required' => true,
        ])
            ->add('description', 'textarea', [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 10],
            ])
            ->add('titre', 'text', [
                'label' => 'Titre',
                'required' => false,
            ])
            ->add('appelation', 'entity', [
                'label' => 'Cette appelation dépend de',
                'required' => false,
                'class' => \App\Entity\Appelation::class,
                'property' => 'label',
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\Appelation::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'appelation';
    }
}
