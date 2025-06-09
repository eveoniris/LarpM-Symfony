<?php


namespace App\Form\Type;

use App\Entity\ObjetCarac;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ObjetCaracType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('taille', IntegerType::class, ['required' => false])
            ->add('poid', TextType::class, ['required' => false])
            ->add('couleur', TextType::class, ['required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ObjetCarac::class,
        ]);
    }

    public function getName(): string
    {
        return 'tag';
    }
}
