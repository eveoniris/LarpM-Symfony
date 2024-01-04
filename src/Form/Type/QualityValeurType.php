<?php


namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Type\QualityValeurType.
 *
 * @author kevin
 */
class QualityValeurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('monnaie', 'entity', [
            'label' => 'Choisissez la monnaie',
            'required' => true,
            'class' => \App\Entity\Monnaie::class,
            'property' => 'label',
        ])
            ->add('nombre', 'integer', [
                'label' => 'Quantité',
                'required' => true,
            ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\QualityValeur::class,
        ]);
    }

    public function getName(): string
    {
        return 'qualityValeur';
    }
}
