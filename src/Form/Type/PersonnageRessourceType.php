<?php


namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
            ->add('nombre', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                'label' => 'quantite',
                'required' => true,
            ])
            ->add('ressource', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'label' => 'Choisissez la ressource',
                'required' => true,
                'class' => \App\Entity\Ressource::class,
                'choice_label' => 'label',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
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
