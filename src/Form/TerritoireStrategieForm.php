<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\TerritoireStrategieForm.
 *
 * @author kevin
 */
class TerritoireStrategieForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('tresor', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
            'label' => 'Richesse',
            'required' => true,
        ])
            ->add('resistance', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                'label' => 'Resistance',
                'required' => true,
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\Territoire::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'territoireStrategie';
    }
}
