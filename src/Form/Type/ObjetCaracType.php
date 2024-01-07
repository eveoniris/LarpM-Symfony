<?php


namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Type\ObjetCaracType.
 *
 * @author kevin
 */
class ObjetCaracType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('taille', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, ['required' => false])
            ->add('poid', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, ['required' => false])
            ->add('couleur', \Symfony\Component\Form\Extension\Core\Type\TextType::class, ['required' => false]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\ObjetCarac::class,
        ]);
    }

    public function getName(): string
    {
        return 'tag';
    }
}
