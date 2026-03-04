<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\IntrigueHasGroupe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Type\IntrigueHasGroupeType.
 *
 * @author kevin
 */
class IntrigueHasGroupeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('groupe', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'label' => 'Ajouter un groupe concerné par cette intrigue',
            'required' => true,
            'class' => \App\Entity\Groupe::class,
            'choice_label' => 'nom',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IntrigueHasGroupe::class,
        ]);
    }
}
