<?php


namespace App\Form\Rule;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\Rule\RuleForm.
 *
 * @author kevin
 */
class RuleUpdateForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => 'Choisissez un titre',
                'required' => true,
            ])
            ->add('description', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
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
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => '\\'.\App\Entity\rule::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'ruleUpdate';
    }
}
