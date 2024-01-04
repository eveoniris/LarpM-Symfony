<?php


namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Type\PersonnageRessourceType.
 *
 * @author kevin
 */
class PersonnageRessourceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', 'integer', [
                'label' => 'quantite',
                'required' => true,
            ])
            ->add('ressource', 'entity', [
                'label' => 'Choisissez la ressource',
                'required' => true,
                'class' => \App\Entity\Ressource::class,
                'property' => 'label',
            ]);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\PersonnageRessource::class,
        ]);
    }

    public function getName(): string
    {
        return 'personnageRessource';
    }
}
