<?php


namespace App\Form;

use App\Entity\Priere;
use App\Entity\Sphere;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PriereForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('sphere', EntityType::class, [
            'required' => true,
            'label' => 'Sphère',
            'autocomplete' => true,
            'class' => Sphere::class,
            'choice_label' => 'label',
        ])
            ->add('niveau', ChoiceType::class, [
                'required' => true,
                'choices' => ['1' => 1, '2' => 2, '3' => 3, '4' => 4], // TODO true Entity or Enum?
                'label' => 'Niveau',
            ])
            ->add('label', TextType::class, [
                'required' => true,
                'label' => 'Label',
            ])
            ->add('file', FileType::class, [
                'label' => 'Téléversez un document',
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description',
                'empty_data' => '',
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 9],
            ])
            ->add('annonce', TextareaType::class, [
                'required' => false,
                'label' => 'Annonce',
                'empty_data' => '',
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
            'data_class' => Priere::class,
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
        return 'priere';
    }
}
