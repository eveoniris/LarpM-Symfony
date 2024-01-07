<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\PriereForm.
 *
 * @author kevin
 */
class PriereForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('sphere', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
            'required' => true,
            'label' => 'Sphère',
            'class' => \App\Entity\Sphere::class,
            'property' => 'label',
        ])
            ->add('niveau', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'required' => true,
                'choices' => ['1' => 1, '2' => 2, '3' => 3, '4' => 4],
                'label' => 'Niveau',
            ])
            ->add('label', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'required' => true,
                'label' => 'Label',
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
                    'class' => 'tinymce',
                    'rows' => 9],
            ])
            ->add('annonce', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'required' => false,
                'label' => 'Annonce',
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
            'data_class' => \App\Entity\Priere::class,
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
