<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\ChronologieForm.
 *
 * @author kevin
 */
class ChronologieForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('territoire', 'entity', [
                'required' => true,
                'label' => 'Territoire',
                'class' => \App\Entity\Territoire::class,
                'property' => 'nom',
            ])
            ->add('description', 'textarea', [
                'required' => true,
                'label' => 'Description',
                'attr' => ['rows' => 9],
            ])
            ->add('year', 'integer', [
                'required' => true,
                'label' => 'Année',
            ])
            ->add('month', 'integer', [
                'required' => false,
                'label' => 'Mois (falcultatif)',
            ])
            ->add('day', 'integer', [
                'required' => false,
                'label' => 'Jour (falcultatif)',
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\Chronologie::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'chronologie';
    }
}
