<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\TrombineForm.
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
            ->add('label', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => 'Choisissez un titre',
                'required' => true,
            ])
            ->add('description', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'label' => 'Ecrivez une petite description',
                'required' => true,
                'attr' => [
                    // TODO 'class' => 'tinymce',
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
