<?php

namespace App\Form;

use App\Entity\Classe;
use App\Entity\CompetenceFamily;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClasseForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label_masculin', TextType::class, [
            'required' => true,
        ])
            ->add('image_m', TextType::class, [
                'label' => 'Adresse de l\'image utilisé pour représenté cette classe',
                'required' => true,
            ])
            ->add('label_feminin', TextType::class, [
                'required' => true,
            ])
            ->add('image_f', TextType::class, [
                'label' => 'Adresse de l\'image utilisé pour représenté cette classe',
                'required' => true,
            ])
            ->add('description', TextareaType::class, [
                'required' => false, ]
            )
            ->add('competenceFamilyFavorites', EntityType::class, [
                'label' => "Famille de compétences favorites (n'oubliez pas de cochez aussi la/les compétences acquises à la création)",
                'required' => false,
                'choice_label' => 'label',
                'multiple' => true,
                'expanded' => true,
                'mapped' => true,
                'class' => CompetenceFamily::class, ]
            )
            ->add('competenceFamilyNormales', EntityType::class, [
                'label' => 'Famille de compétences normales',
                'required' => false,
                'choice_label' => 'label',
                'multiple' => true,
                'expanded' => true,
                'mapped' => true,
                'class' => CompetenceFamily::class, ]
            )
            ->add('competenceFamilyCreations', EntityType::class, [
                'label' => 'Famille de compétences acquises à la création',
                'required' => false,
                'choice_label' => 'label',
                'multiple' => true,
                'expanded' => true,
                'mapped' => true,
                'class' => CompetenceFamily::class, ]
            );
    }

    /**
     * Définition de l'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Classe::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'classe';
    }
}
