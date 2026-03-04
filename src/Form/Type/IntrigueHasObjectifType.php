<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\IntrigueHasObjectif;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Type\IntrigueHasObjectifType.
 *
 * @author kevin
 */
class IntrigueHasObjectifType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('objectif', ObjectifType::class, [
            'label' => 'Ajouter un objectif',
            'required' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IntrigueHasObjectif::class,
        ]);
    }
}
