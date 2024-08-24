<?php


namespace App\Form;

use App\Entity\Domaine;
use App\Entity\Sort;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', TextType::class, [
            'required' => true,
            'label' => 'Label',
        ])
            ->add('niveau', ChoiceType::class, [
                'required' => true,
                'choices' => ['1' => 1, '2' => 2, '3' => 3, '4' => 4],
                'label' => 'Niveau',
            ])
            ->add('domaine', EntityType::class, [
                'required' => true,
                'label' => 'Domaine',
                'class' => Domaine::class,
                'choice_label' => 'label',
            ])
            ->add('file', FileType::class, [
                'label' => 'Téléversez un document',
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description',
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 9,
                ],
            ])
            ->add('secret', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    false,
                    true,
                ],
                'choice_label' => static fn ($value) => match ($value) {
                    false => 'Visible',
                    true => 'Secret',
                },
                'label' => 'Secret',
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sort::class,
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
        return 'sort';
    }
}
