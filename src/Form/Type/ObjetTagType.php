<?php


namespace App\Form\Type;

use App\Form\Type\TagType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Type\ObjetTagType.
 *
 * @author kevin
 */
class ObjetTagType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('tag', TagType::class, [
            'label' => 'Ajouter un tag',
            'required' => true,
        ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => 'App\Entity\ObjetTag',
        ]);
    }

    public function getName(): string
    {
        return 'ObjetTag';
    }
}
