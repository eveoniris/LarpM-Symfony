<?php

declare(strict_types=1);

namespace App\Form\Territoire;

use App\Entity\Territoire;
use App\Repository\TerritoireRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TerritoireFrontaliersCulturelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('frontaliersCulturels', EntityType::class, [
            'required' => false,
            'label' => false,
            'class' => Territoire::class,
            'multiple' => true,
            'expanded' => false,
            'mapped' => true,
            'autocomplete' => true,
            'choice_label' => 'nom',
            'query_builder' => static fn (TerritoireRepository $tr) => $tr->createQueryBuilder('t')->orderBy('t.nom', 'ASC'),
        ])->add('searchFrontalier', EntityType::class, [
            'mapped' => false,
            'required' => false,
            'label' => false,
            'class' => Territoire::class,
            'multiple' => false,
            'autocomplete' => true,
            'choice_label' => 'nom',
            'placeholder' => 'Tapez un nom de territoire…',
            'query_builder' => static fn (TerritoireRepository $tr) => $tr->createQueryBuilder('t')->orderBy('t.nom', 'ASC'),
            'attr' => ['data-search-field' => 'frontaliers'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Territoire::class,
        ]);
    }
}
