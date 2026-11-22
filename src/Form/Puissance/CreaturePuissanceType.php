<?php

declare(strict_types=1);

namespace App\Form\Puissance;

use App\Entity\CreaturePuissance;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreaturePuissanceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, ['required' => true, 'label' => 'Créature'])
            ->add('famille', TextType::class, ['required' => false, 'label' => 'Famille ou précision (ex. Morts vivants)'])
            ->add('xp', IntegerType::class, ['required' => true, 'label' => 'Équivalent XP'])
            ->add('notes', TextareaType::class, [
                'required' => false,
                'label' => 'Notes',
                'attr' => ['rows' => 4],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreaturePuissance::class,
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
