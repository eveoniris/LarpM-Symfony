<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\IntrigueHasGroupeSecondaire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Type\IntrigueHasGroupeSecondaireType.
 *
 * @author kevin
 */
class IntrigueHasGroupeSecondaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('secondaryGroup', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'label' => 'Ajouter un groupe transverse concerné par cette intrigue',
            'required' => true,
            'class' => \App\Entity\SecondaryGroup::class,
            'choice_label' => 'label',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IntrigueHasGroupeSecondaire::class,
        ]);
    }

    public function getName(): string
    {
        return 'intrigueHasGroupeSecondaire';
    }
}
