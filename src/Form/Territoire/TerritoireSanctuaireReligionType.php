<?php

declare(strict_types=1);

namespace App\Form\Territoire;

use App\Entity\Religion;
use App\Entity\Territoire;
use App\Repository\ReligionRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TerritoireSanctuaireReligionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('sanctuaireReligion', EntityType::class, [
            'required'      => false,
            'label'         => 'Religion du sanctuaire',
            'class'         => Religion::class,
            'choice_label'  => 'label',
            'placeholder'   => 'Aucune religion',
            'autocomplete'  => true,
            'query_builder' => static fn (ReligionRepository $rr) => $rr->createQueryBuilder('r')->orderBy('r.label', 'ASC'),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Territoire::class]);
    }
}
