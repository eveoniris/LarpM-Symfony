<?php


namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Type\ClasseType.
 *
 * @author kevin
 */
class ClasseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('classe', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'label' => false,
            'required' => true,
            'choice_label' => 'label',
            'class' => \App\Entity\Classe::class,
        ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\GroupeClasse::class,
        ]);
    }

    public function getName(): string
    {
        return 'groupeClasse';
    }
}
