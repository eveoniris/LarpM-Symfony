<?php

namespace App\Form\Technologie;

use App\Entity\CompetenceFamily;
use App\Entity\Technologie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TechnologieForm extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', TextType::class, [
            'required' => true,
            'label' => 'Nom',
        ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description succinte',
            ])
            ->add('competenceFamily', EntityType::class, [
                'class' => CompetenceFamily::class,
                'required' => true,
                'label' => 'Compétence Expert requise',
                'choice_label' => 'label',
            ])
            ->add('secret', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    false,
                    true,
                ],
                'choice_label' => static fn ($value) => match ($value) {
                    false => 'Visible',
                    true => 'Secret',
                },
                'label' => 'Technologie secrète ?',
            ])
            ->add('file', FileType::class, [
                'label' => 'Téléversez un document',
                'required' => true,
            ]);
    }

    /**
     * Définition de l'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Technologie::class,
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
        return 'technologie';
    }
}
