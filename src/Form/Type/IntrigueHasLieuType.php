<?php


namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Type\IntrigueHasLieuType.
 *
 * @author kevin
 */
class IntrigueHasLieuType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('lieu', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'label' => 'Ajouter un lieu concerné par cette intrigue',
            'required' => true,
            'class' => \App\Entity\Lieu::class,
            'property' => 'nom',
        ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\IntrigueHasLieu::class,
        ]);
    }

    public function getName(): string
    {
        return 'intrigueHasLieu';
    }
}
