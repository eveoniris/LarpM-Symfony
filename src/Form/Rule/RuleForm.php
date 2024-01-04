<?php


namespace App\Form\Rule;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\Rule\RuleForm.
 *
 * @author kevin
 */
class RuleForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('rule', 'file', [
            'label' => 'Choisissez votre fichier',
            'required' => true,
        ])
            ->add('label', 'text', [
                'label' => 'Choisissez un titre',
                'required' => true,
            ])
            ->add('description', 'textarea', [
                'label' => 'Ecrivez une petite description',
                'required' => true,
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 5,
                ],
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'rule';
    }
}
