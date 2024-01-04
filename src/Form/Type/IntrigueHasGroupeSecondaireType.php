<?php


namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Type\IntrigueHasGroupeSecondaireType.
 *
 * @author kevin
 */
class IntrigueHasGroupeSecondaireType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('secondaryGroup', 'entity', [
            'label' => 'Ajouter un groupe secondaire concerné par cette intrigue',
            'required' => true,
            'class' => \App\Entity\SecondaryGroup::class,
            'property' => 'label',
        ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\IntrigueHasGroupeSecondaire::class,
        ]);
    }

    public function getName(): string
    {
        return 'intrigueHasGroupeSecondaire';
    }
}
