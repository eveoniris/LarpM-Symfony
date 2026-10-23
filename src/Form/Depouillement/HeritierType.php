<?php

declare(strict_types=1);

namespace App\Form\Depouillement;

use App\Entity\Genre;
use App\Entity\Langue;
use App\Entity\Lignee;
use App\Entity\Territoire;
use App\Repository\TerritoireRepository;
use App\Service\HeritierSaisie;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Un héritier à créer : nom, genre, origine, langue des parents et lignée (facultative).
 *
 * @extends AbstractType<HeritierSaisie>
 */
class HeritierType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, ['label' => 'Nom', 'required' => $options['obligatoire'], 'empty_data' => ''])
            ->add('genre', EntityType::class, [
                'label' => 'Genre',
                'class' => Genre::class,
                'choice_label' => 'label',
                'required' => $options['obligatoire'],
                'placeholder' => '- Choisir -',
            ])
            ->add('territoire', EntityType::class, [
                'label' => 'Origine',
                'class' => Territoire::class,
                'choice_label' => 'nom',
                'required' => $options['obligatoire'],
                'placeholder' => '- Choisir -',
                'query_builder' => static fn (TerritoireRepository $er) => $er->createQueryBuilder('t')->andWhere('t.territoire IS NULL')->orderBy('t.nom', 'ASC'),
            ])
            ->add('langueParents', EntityType::class, [
                'label' => 'Langue des parents',
                'class' => Langue::class,
                'choice_label' => 'label',
                'required' => false,
                'placeholder' => '- Aucune -',
            ])
            ->add('lignee', EntityType::class, [
                'label' => 'Lignée',
                'class' => Lignee::class,
                'choice_label' => 'nom',
                'required' => false,
                'placeholder' => '- Aucune -',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => HeritierSaisie::class, 'obligatoire' => true]);
        $resolver->setAllowedTypes('obligatoire', 'bool');
    }
}
