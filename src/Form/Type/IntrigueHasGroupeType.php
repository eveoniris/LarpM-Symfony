<?php


namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
            'property' => 'nom',
        ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\IntrigueHasGroupe::class,
        ]);
    }

    public function getName(): string
    {
        return 'intrigueHasGroupe';
    }
}
