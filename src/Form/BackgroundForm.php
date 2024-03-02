<?php

namespace App\Form;

use App\Entity\Background;
use App\Entity\Gn;
use App\Entity\Groupe;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\BackgroundForm.
 *
 * @author kevin
 */
class BackgroundForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('titre', TextType::class, [
            'required' => true,
            'label' => 'Titre',
        ])
            ->add('text', TextareaType::class, [
                'required' => true,
                'label' => 'Contenu',
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 15],
            ])
            ->add('groupe', EntityType::class, [
                'required' => true,
                'label' => 'Groupe',
                'class' => Groupe::class,
                'choice_label' => 'nom',
            ])
            ->add('gn', EntityType::class, [
                'required' => true,
                'label' => 'GN',
                'class' => Gn::class,
                'choice_label' => 'label',
                'placeholder' => 'Choisissez le GN auquel est lié ce background',
                'empty_data' => null,
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Background::class,
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
        return 'background';
    }
}
