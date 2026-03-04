<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Lieu;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class LieuType extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nom', TextType::class, [
            'required' => true,
            'attr' => ['maxlength' => 45],
            'constraints' => [
                new Length(['max' => 45]),
                new NotBlank(),
            ],
        ])->add('description', TextareaType::class, [
            'required' => true,
            'attr' => [
                'class' => 'tinymce',
                'rows' => 9,
            ],
        ]);
    }

    /**
     * Définition de la classe d'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Lieu::class,
            'attr' => [
                'novalidate' => 'novalidate',
            ],
        ]);
    }

    /*
     * Nom du formlaire.
     */
}
