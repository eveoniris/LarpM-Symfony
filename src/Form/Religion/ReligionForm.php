<?php


namespace App\Form\Religion;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\ReligionForm.
 *
 * @author kevin
 */
class ReligionForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'label' => 'Label',
            'required' => true,
        ])
            ->add('description', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'label' => 'Description rapide',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 10],
            ])
            ->add('description_orga', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'label' => 'Description pour les ORGAS',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 10],
            ])
            ->add('description_pratiquant', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'label' => 'Description pour les PRATIQUANTS',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 10],
            ])
            ->add('description_fervent', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'label' => 'Description pour les FERVENTS',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 10],
            ])
            ->add('description_fanatique', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'label' => 'Description pour les FANATIQUES',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 10],
            ])
            ->add('spheres', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'label' => 'Sphères',
                'multiple' => true,
                'expanded' => true,
                'class' => \App\Entity\Sphere::class,
                'choice_label' => 'label',
            ])
            ->add('secret', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
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
            'data_class' => \App\Entity\Religion::class,
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
