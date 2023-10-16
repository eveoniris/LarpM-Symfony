<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\ConnaissanceForm.
 *
 * @author Kevin F.
 */
class ConnaissanceForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', 'text', [
            'required' => true,
            'label' => 'Label',
        ])
            ->add('document', 'file', [
                'label' => 'Téléversez un document',
                'required' => true,
                'mapped' => false,
            ])
            ->add('description', 'textarea', [
                'required' => false,
                'label' => 'Description',
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 5,
                ],
            ])
            ->add('contraintes', 'textarea', [
                'required' => false,
                'label' => 'Contraintes',
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 5,
                ],
            ])
            ->add('secret', 'choice', [
                'required' => true,
                'choices' => [
                    false => 'Connaissance visible',
                    true => 'Connaissance secrète',
                ],
                'label' => 'Secret',
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\Connaissance::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'connaissance';
    }
}
