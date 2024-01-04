<?php


namespace App\Form\Stock;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Type\ObjetDelete.
 *
 * @author kevin
 */
class ObjetDeleteForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('delete', 'submit', ['label' => 'Supprimer']);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\Objet::class,
            'cascade_validation' => true,
        ]);
    }

    public function getName(): string
    {
        return 'objetDelete';
    }
}
