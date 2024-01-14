<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\ClasseForm.
 *
 * @author kevin
 */
class ClasseForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label_masculin', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'required' => true,
        ])
            ->add('image_m', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => 'Adresse de l\'image utilisé pour représenté cette classe',
                'required' => true,
            ])
            ->add('label_feminin', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'required' => true,
            ])
            ->add('image_f', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => 'Adresse de l\'image utilisé pour représenté cette classe',
                'required' => true,
            ])
            ->add('description', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                    'required' => false, ]
            )
            ->add('competenceFamilyFavorites', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                    'label' => "Famille de compétences favorites (n'oubliez pas de cochez aussi la/les compétences acquises à la création)",
                    'required' => false,
                    'choice_label' => 'label',
                    'multiple' => true,
                    'expanded' => true,
                    'mapped' => true,
                    'class' => \App\Entity\CompetenceFamily::class, ]
            )
            ->add('competenceFamilyNormales', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                    'label' => 'Famille de compétences normales',
                    'required' => false,
                    'choice_label' => 'label',
                    'multiple' => true,
                    'expanded' => true,
                    'mapped' => true,
                    'class' => \App\Entity\CompetenceFamily::class, ]
            )
            ->add('competenceFamilyCreations', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                    'label' => 'Famille de compétences acquises à la création',
                    'required' => false,
                    'choice_label' => 'label',
                    'multiple' => true,
                    'expanded' => true,
                    'mapped' => true,
                    'class' => \App\Entity\CompetenceFamily::class, ]
            );
    }

    /**
     * Définition de l'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\Classe::class,
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
