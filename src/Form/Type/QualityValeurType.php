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
        $builder->add('monnaie', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'label' => 'Choisissez la monnaie',
            'required' => true,
            'class' => \App\Entity\Monnaie::class,
            'property' => 'label',
        ])
            ->add('nombre', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
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
