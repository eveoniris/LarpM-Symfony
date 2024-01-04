<?php


namespace App\Form\Type;

use App\Form\Type\EvenementType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Type\IntrigueHasEvenementType.
 *
 * @author kevin
 */
class IntrigueHasEvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('evenement', EvenementType::class, [
            'label' => 'Ajouter un événement concerné par cette intrigue',
            'required' => true,
        ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\IntrigueHasEvenement::class,
        ]);
    }

    public function getName(): string
    {
        return 'intrigueHasEvenement';
    }
}
