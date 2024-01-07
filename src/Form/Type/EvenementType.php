<?php


namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Type\EvenementType.
 *
 * @author kevin
 */
class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('text', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'label' => 'Description de l\'événement',
            'required' => true,
        ])
            ->add('date', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => 'Date de l\'événement',
                'required' => false,
            ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\Evenement::class,
        ]);
    }

    public function getName(): string
    {
        return 'Evenement';
    }
}
