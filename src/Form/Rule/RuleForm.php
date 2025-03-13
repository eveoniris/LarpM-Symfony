<?php


namespace App\Form\Rule;

use App\Entity\Rule;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RuleForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('file', FileType::class, [
            'label' => 'Choisissez votre fichier',
            'required' => true,
        ])
            ->add('label', TextType::class, [
                'label' => 'Choisissez un titre',
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
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
            'data_class' => Rule::class,
            'attr' => [
                'novalidate' => 'novalidate',
            ],
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'rule';
    }
}
