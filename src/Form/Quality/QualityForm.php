<?php


namespace App\Form\Quality;

use App\Form\Type\QualityValeurType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
        $builder->add('label', 'text', [
            'label' => 'Label',
            'required' => true,
        ])
            ->add('numero', 'integer', [
                'required' => true,
                'label' => 'Numéro',
            ])
            ->add('qualityValeurs', 'collection', [
                'label' => 'Valeur',
                'required' => true,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'type' => new QualityValeurType(),
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
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
