<?php


namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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

    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\Objectif::class,
        ]);
    }

    public function getName(): string
    {
        return 'Objectif';
    }
}
