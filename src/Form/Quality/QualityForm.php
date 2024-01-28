<?php


namespace App\Form\Quality;

use App\Form\Type\QualityValeurType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Quality\QualityForm.
 *
 * @author kevin
 */
class QualityForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'label' => 'Label',
            'required' => true,
        ])
            ->add('numero', \Symfony\Component\Form\Extension\Core\Type\IntegerType::class, [
                'required' => true,
                'label' => 'Numéro',
            ])
            ->add('qualityValeurs', \Symfony\Component\Form\Extension\Core\Type\CollectionType::class, [
                'label' => 'Valeur',
                'required' => true,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'entry_type' => QualityValeurType::class,
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\Quality::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'quality';
    }
}
