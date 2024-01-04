<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * LarpManager\Form\CompetenceForm.
 *
 * @author kevin
 */
class CompetenceForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('competenceFamily', 'entity', [
            'label' => 'Famille',
            'required' => true,
            'class' => \App\Entity\CompetenceFamily::class,
            'property' => 'label',
        ])
            ->add('level', 'entity', [
                'label' => 'Niveau',
                'required' => true,
                'class' => \App\Entity\Level::class,
                'property' => 'label',
            ])
            ->add('description', 'textarea', [
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                ],
            ])
            ->add('document', 'file', [
                'label' => 'Téléversez un document',
                'required' => true,
                'mapped' => false,
            ])
            ->add('materiel', 'textarea', [
                'label' => 'Matériel necessaire',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                ],
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver): void
    {
        $resolver->setDefaults([
            'class' => \App\Entity\Competence::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'competence';
    }
}
