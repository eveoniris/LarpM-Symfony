<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\BackgroundForm.
 *
 * @author kevin
 */
class BackgroundForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('titre', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'required' => true,
            'label' => 'Titre',
        ])
            ->add('text', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'required' => true,
                'label' => 'Contenu',
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 15],
            ])
            ->add('groupe', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => true,
                'label' => 'Groupe',
                'class' => \App\Entity\Groupe::class,
                'choice_label' => 'nom',
            ])
            ->add('gn', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => true,
                'label' => 'GN',
                'class' => \App\Entity\Gn::class,
                'choice_label' => 'label',
                'placeholder' => 'Choisissez le GN auquel est lié ce background',
                'empty_data' => null,
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\Background::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'background';
    }
}
