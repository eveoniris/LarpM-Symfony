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
        $builder->add('label', 'text', [
            'label' => 'Label',
            'required' => true,
        ])
            ->add('description', 'textarea', [
                'label' => 'Description rapide',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 10],
            ])
            ->add('description_orga', 'textarea', [
                'label' => 'Description pour les ORGAS',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 10],
            ])
            ->add('description_pratiquant', 'textarea', [
                'label' => 'Description pour les PRATIQUANTS',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 10],
            ])
            ->add('description_fervent', 'textarea', [
                'label' => 'Description pour les FERVENTS',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 10],
            ])
            ->add('description_fanatique', 'textarea', [
                'label' => 'Description pour les FANATIQUES',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 10],
            ])
            ->add('spheres', 'entity', [
                'label' => 'Sphères',
                'multiple' => true,
                'expanded' => true,
                'class' => \App\Entity\Sphere::class,
                'choice_label' => 'label',
            ])
            ->add('secret', 'choice', [
                'required' => true,
                'choices' => [
                    false => 'Religion publique',
                    true => 'Religion secrète',
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
