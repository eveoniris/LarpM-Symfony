<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\SortForm.
 *
 * @author kevin
 */
class SortForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'required' => true,
            'label' => 'Label',
        ])
            ->add('niveau', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'required' => true,
                'choices' => ['1' => 1, '2' => 2, '3' => 3, '4' => 4],
                'label' => 'Niveau',
            ])
            ->add('domaine', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => true,
                'label' => 'Domaine',
                'class' => \App\Entity\Domaine::class,
                'choice_label' => 'label',
            ])
            ->add('document', 'file', [
                'label' => 'Téléversez un document',
                'required' => true,
                'mapped' => false,
            ])
            ->add('description', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'required' => false,
                'label' => 'Description',
                'attr' => [
                    // TODO 'class' => 'tinymce',
                    'rows' => 9,
                ],
            ])
            ->add('secret', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'required' => true,
                'choices' => [
                    false => 'Sort visible',
                    true => 'Sort secret',
                ],
                'label' => 'Secret',
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\Sort::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'sort';
    }
}
