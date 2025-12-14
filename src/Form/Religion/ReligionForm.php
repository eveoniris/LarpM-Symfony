<?php


namespace App\Form\Religion;

use App\Entity\Religion;
use App\Entity\Sphere;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReligionForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', TextType::class, [
            'label' => 'Label',
            'required' => true,
        ])
            ->add('description', TextareaType::class, [
                'label' => 'Description rapide',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 10],
            ])
            ->add('description_orga', TextareaType::class, [
                'label' => 'Description pour les ORGAS',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 10],
            ])
            ->add('description_pratiquant', TextareaType::class, [
                'label' => 'Description pour les PRATIQUANTS',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 10],
            ])
            ->add('description_fervent', TextareaType::class, [
                'label' => 'Description pour les FERVENTS',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 10],
            ])
            ->add('description_fanatique', TextareaType::class, [
                'label' => 'Description pour les FANATIQUES',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 10],
            ])
            ->add('spheres', EntityType::class, [
                'label' => 'Sphères',
                'multiple' => true,
                'expanded' => true,
                'class' => Sphere::class,
                'choice_label' => 'label',
            ])
            ->add('secret', ChoiceType::class, [
                'required' => true,
                'choices' => [
                    'Religion publique' => false,
                    'Religion secrète' => true,
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
            'data_class' => Religion::class,
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
        return 'religion';
    }
}
