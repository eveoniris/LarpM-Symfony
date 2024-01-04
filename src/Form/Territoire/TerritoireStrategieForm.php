<?php


namespace App\Form\Territoire;

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
        $builder->add('tresor', 'integer', [
            'label' => 'Richesse',
            'required' => true,
        ])
            ->add('resistance', 'integer', [
                'label' => 'Resistance',
                'required' => true,
            ])
            ->add('ordreSocial', 'integer', [
                'label' => 'Ordre social',
                'required' => false,
                'attr' => [
                    'min' => 1,
                    'max' => 5,
                ],
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
