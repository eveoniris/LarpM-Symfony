<?php


namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Type\CultureHasClasseType.
 *
 * @author kevin
 */
class CultureHasClasseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('classe', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'label' => 'Choisissez la classe',
                'required' => true,
                'class' => \App\Entity\Classe::class,
                'property' => 'label',
                'query_builder' => static function (\LarpManager\Repository\ClasseRepository $er) {
                    $qb = $er->createQueryBuilder('c');
                    $qb->where('c.creation is true');
                    $qb->orderBy('c.label_masculin', 'ASC');

                    return $qb;
                },
            ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\CultureHasClasse::class,
        ]);
    }

    public function getName(): string
    {
        return 'cultureHasClasse';
    }
}
