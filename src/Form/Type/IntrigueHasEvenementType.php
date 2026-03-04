<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\IntrigueHasEvenement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Type\IntrigueHasEvenementType.
 *
 * @author kevin
 */
class IntrigueHasEvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('evenement', EvenementType::class, [
            'label' => 'Ajouter un événement concerné par cette intrigue',
            'required' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IntrigueHasEvenement::class,
        ]);
    }

}
