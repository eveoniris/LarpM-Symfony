<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;

/**
 * LarpManager\Form\CompetenceFamilyForm.
 *
 * @author kevin
 */
class CompetenceFamilyForm extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['sanitize_html' => true]);
    }

    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', 'text', [
            'required' => true,
            'attr' => ['maxlength' => 45],
            'constraints' => [new Length(['max' => 45])],
        ])
            ->add('description', 'textarea', [
                'required' => false,
                'constraints' => [new Length(['max' => 450])],
                'attr' => [
                    'class' => 'tinymce',
                    'row' => 9,
                    'maxlength' => 450,
                ],
            ]);
    }

    /**
     * Définition de l'entité concernée.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\CompetenceFamily::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'competenceFamily';
    }
}
