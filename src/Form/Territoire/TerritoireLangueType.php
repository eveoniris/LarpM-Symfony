<?php

declare(strict_types=1);

namespace App\Form\Territoire;

use App\Entity\Langue;
use App\Entity\Territoire;
use App\Repository\LangueRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Modification des langues (principale et parlées) d'un territoire.
 */
class TerritoireLangueType extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('languePrincipale', EntityType::class, [
                'required' => false,
                'label' => 'Langue principale',
                'class' => Langue::class,
                'multiple' => false,
                'mapped' => true,
                'choice_label' => 'label',
                'query_builder' => static fn (LangueRepository $lr) => $lr->createQueryBuilder('lr')->orderBy('lr.label', 'ASC'),
            ])
            ->add('langues', EntityType::class, [
                'required' => false,
                'label' => 'Langues parlées (selectionnez aussi la langue principale)',
                'class' => Langue::class,
                'multiple' => true,
                'expanded' => true,
                'mapped' => true,
                'choice_label' => 'label',
                'query_builder' => static fn (LangueRepository $lr) => $lr->createQueryBuilder('lr')->orderBy('lr.label', 'ASC'),
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Territoire::class,
        ]);
    }
}
