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
        $builder->add('taille', 'integer', ['required' => false])
            ->add('poid', 'integer', ['required' => false])
            ->add('couleur', 'text', ['required' => false]);
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
