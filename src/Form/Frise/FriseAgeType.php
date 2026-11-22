<?php

declare(strict_types=1);

namespace App\Form\Frise;

use App\Entity\FriseAge;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FriseAgeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, ['required' => true, 'label' => "Nom de l'âge"])
            ->add('periode', TextType::class, ['required' => false, 'label' => 'Période (ex. -20 000 à -18 000)'])
            ->add('ordre', IntegerType::class, ['required' => true, 'label' => 'Ordre dans la frise (du plus ancien au plus récent)']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FriseAge::class,
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
