<?php


namespace App\Form\Potion;

use App\Entity\Potion;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PotionForm extends AbstractType
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
            ->add('numero', TextType::class, [
                'required' => true,
                'label' => 'Numéro',
            ])
            ->add('niveau', ChoiceType::class, [
                'required' => true,
                'choices' => ['1' => 1, '2' => 2, '3' => 3, '4' => 4],
                'label' => 'Niveau',
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
                    'rows' => 9],
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Potion::class,
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
        return 'potion';
    }
}
