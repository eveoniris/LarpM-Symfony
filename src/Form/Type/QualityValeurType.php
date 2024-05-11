<?php


namespace App\Form\Type;

use App\Entity\Monnaie;
use App\Entity\QualityValeur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Type\QualityValeurType.
 *
 * @author kevin
 */
class QualityValeurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('monnaie', EntityType::class, [
            'label' => 'Choisissez la monnaie',
            'required' => true,
            'class' => Monnaie::class,
            'choice_label' => 'label',
            ])
            ->add('nombre', IntegerType::class, [
                'label' => 'Quantité',
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => QualityValeur::class,
        ]);
    }

    public function getName(): string
    {
        return 'qualityValeur';
    }
}
