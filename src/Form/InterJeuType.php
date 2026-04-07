<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\InterJeu;
use App\Enum\InterJeuEtat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class InterJeuType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'required' => true,
            ])
            ->add('anneeJeu', IntegerType::class, [
                'label' => 'Année en jeu',
                'required' => true,
            ])
            ->add('dateReel', DateType::class, [
                'label' => 'Date réelle',
                'required' => true,
                'widget' => 'single_text',
            ])
            ->add('etat', EnumType::class, [
                'label' => 'État',
                'required' => true,
                'class' => InterJeuEtat::class,
                'choice_label' => static fn (InterJeuEtat $e) => $e->getLabel(),
            ])
            ->add('informationComplementaire', TextareaType::class, [
                'label' => 'Informations complémentaires',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 9,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InterJeu::class,
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
