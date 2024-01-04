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
        $builder->add('label_masculin', 'text', [
            'required' => true,
        ])
            ->add('image_m', 'text', [
                'label' => 'Adresse de l\'image utilisé pour représenté cette classe',
                'required' => true,
            ])
            ->add('label_feminin', 'text', [
                'required' => true,
            ])
            ->add('image_f', 'text', [
                'label' => 'Adresse de l\'image utilisé pour représenté cette classe',
                'required' => true,
            ])
            ->add('description', 'textarea', [
                    'required' => false, ]
            )
            ->add('competenceFamilyFavorites', 'entity', [
                    'label' => "Famille de compétences favorites (n'oubliez pas de cochez aussi la/les compétences acquises à la création)",
                    'required' => false,
                    'property' => 'label',
                    'multiple' => true,
                    'expanded' => true,
                    'mapped' => true,
                    'class' => \App\Entity\CompetenceFamily::class, ]
            )
            ->add('competenceFamilyNormales', 'entity', [
                    'label' => 'Famille de compétences normales',
                    'required' => false,
                    'property' => 'label',
                    'multiple' => true,
                    'expanded' => true,
                    'mapped' => true,
                    'class' => \App\Entity\CompetenceFamily::class, ]
            )
            ->add('competenceFamilyCreations', 'entity', [
                    'label' => 'Famille de compétences acquises à la création',
                    'required' => false,
                    'property' => 'label',
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
