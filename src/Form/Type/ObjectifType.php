<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\Objectif;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Type\ObjectifType.
 *
 * @author kevin
 */
class ObjectifType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('text', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'label' => "Description de l'objectif",
            'required' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Objectif::class,
        ]);
    }

    public function getName(): string
    {
        return 'Objectif';
    }
}
