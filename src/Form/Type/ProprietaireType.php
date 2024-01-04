<?php


namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
            ->add('adresse', 'text', ['required' => false])
            ->add('mail', 'text', ['required' => false])
            ->add('tel', 'text', ['required' => false]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver): void
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
