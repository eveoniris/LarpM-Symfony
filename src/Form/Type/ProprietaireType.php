<?php


namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Type\ProprietaireType.
 *
 * @author kevin
 */
class ProprietaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nom', 'text')
            ->add('adresse', \Symfony\Component\Form\Extension\Core\Type\TextType::class, ['required' => false])
            ->add('mail', \Symfony\Component\Form\Extension\Core\Type\TextType::class, ['required' => false])
            ->add('tel', \Symfony\Component\Form\Extension\Core\Type\TextType::class, ['required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\Proprietaire::class,
        ]);
    }

    public function getName(): string
    {
        return 'proprietaire';
    }
}
