<?php

namespace App\Form;

use App\Entity\CompetenceFamily;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

/**
 * LarpManager\Form\CompetenceFamilyForm.
 *
 * @author kevin
 */
class CompetenceFamilyForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', TextType::class, [
            'required' => true,
            'attr' => ['maxlength' => 45],
            'constraints' => [new Length(['max' => 45])],
        ])
            ->add('description', TextareaType::class, [
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
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => CompetenceFamily::class,
            'sanitize_html' => true,
            // TinyMce Hide the text field. It's break the form Submit because autovalidate can't allow it
            // Reason : the user can't fill a hidden field, so it's couldn't be "required"
            'attr' => [
                'novalidate' => 'novalidate',
            ],
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
