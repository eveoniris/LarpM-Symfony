<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\LevelForm.
 *
 * @author kevin
 */
class LevelForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', 'text', [
            'required' => true,
        ])
            ->add('index', 'integer', [
                'required' => true,
            ])
            ->add('cout_favori', 'integer', [
                'label' => 'Coût favori',
                'required' => true,
            ])
            ->add('cout', 'integer', [
                'label' => 'Coût normal',
                'required' => true,
            ])
            ->add('cout_meconu', 'integer', [
                'label' => 'Coût méconnu',
                'required' => true,
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'data' => \App\Entity\Level::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'levelForm';
    }
}
