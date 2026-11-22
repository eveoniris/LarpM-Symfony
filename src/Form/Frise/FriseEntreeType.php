<?php

declare(strict_types=1);

namespace App\Form\Frise;

use App\Entity\FriseAge;
use App\Entity\FriseEntree;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FriseEntreeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('age', EntityType::class, [
                'class' => FriseAge::class,
                'choice_label' => 'nom',
                'required' => true,
                'label' => 'Âge',
                'query_builder' => static fn ($repository) => $repository->createQueryBuilder('a')->orderBy('a.ordre', 'ASC'),
            ])
            ->add('titre', TextType::class, ['required' => true, 'label' => 'Peuple, civilisation ou événement'])
            ->add('notes', TextareaType::class, [
                'required' => false,
                'label' => 'Détails pour les scénaristes',
                'attr' => ['rows' => 6, 'class' => 'tinymce'],
            ])
            ->add('ordre', IntegerType::class, ['required' => true, 'label' => "Ordre dans l'âge"]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FriseEntree::class,
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
