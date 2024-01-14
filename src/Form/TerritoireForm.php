<?php


namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * LarpManager\Form\TerritoireForm.
 *
 * @author kevin
 */
class TerritoireForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nom', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
            'label' => 'Nom',
            'required' => true,
        ])
            ->add('appelation', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'label' => "Choisissez l'appelation de ce territoire",
                'required' => true,
                'class' => \App\Entity\Appelation::class,
                'multiple' => false,
                'mapped' => true,
                'choice_label' => 'label',
            ])
            ->add('description', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    // TODO 'class' => 'tinymce',
                    'rows' => 10],
            ])
            ->add('description_secrete', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'label' => 'Description connue des habitants',
                'required' => false,
                'attr' => [
                    // TODO 'class' => 'tinymce',
                    'rows' => 10],
            ])
            ->add('statut', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'label' => 'Statut',
                'required' => false,
                'choices' => ['Normal' => 'Normal', 'Instable' => 'Instable'],
            ])
            ->add('geojson', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'label' => 'GeoJSON',
                'required' => false,
                'attr' => ['rows' => 10],
            ])
            ->add('capitale', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => 'Capitale',
                'required' => false,
            ])
            ->add('politique', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => 'Système politique',
                'required' => false,
            ])
            ->add('dirigeant', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => 'Dirigeant',
                'required' => false,
            ])
            ->add('population', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => 'Population',
                'required' => false,
            ])
            ->add('symbole', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => 'Symbole',
                'required' => false,
            ])
            ->add('tech_level', \Symfony\Component\Form\Extension\Core\Type\TextType::class, [
                'label' => 'Niveau technologique',
                'required' => false,
            ])
            ->add('type_racial', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'label' => 'Type racial',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('inspiration', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'label' => 'Inspiration',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('armes_predilection', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'label' => 'Armes de prédilection',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('vetements', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'label' => 'Vetements',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('noms_masculin', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'label' => 'Noms masculins',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('noms_feminin', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'label' => 'Noms féminins',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('frontieres', \Symfony\Component\Form\Extension\Core\Type\TextareaType::class, [
                'label' => 'Frontières',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('importations', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => false,
                'label' => 'Importations',
                'class' => \App\Entity\Ressource::class,
                'multiple' => true,
                'expanded' => true,
                'mapped' => true,
                'choice_label' => 'label',
            ])
            ->add('exportations', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => false,
                'label' => 'Exportations',
                'class' => \App\Entity\Ressource::class,
                'multiple' => true,
                'expanded' => true,
                'mapped' => true,
                'choice_label' => 'label',
            ])
            ->add('languePrincipale', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => false,
                'label' => 'Langue principale',
                'class' => \App\Entity\Langue::class,
                'multiple' => false,
                'mapped' => true,
                'choice_label' => 'label',
            ])
            ->add('langues', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => false,
                'label' => 'Langues parlées (selectionnez aussi la langue principale)',
                'class' => \App\Entity\Langue::class,
                'multiple' => true,
                'expanded' => true,
                'mapped' => true,
                'choice_label' => 'label',
            ])
            ->add('religionPrincipale', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => false,
                'label' => 'Religion dominante',
                'class' => \App\Entity\Religion::class,
                'multiple' => false,
                'mapped' => true,
                'choice_label' => 'label',
            ])
            ->add('religions', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => false,
                'label' => 'Religions secondaires',
                'class' => \App\Entity\Religion::class,
                'multiple' => true,
                'expanded' => true,
                'mapped' => true,
                'choice_label' => 'label',
            ])
            ->add('territoire', \Symfony\Bridge\Doctrine\Form\Type\EntityType::class, [
                'required' => false,
                'label' => 'Ce territoire dépend de ',
                'class' => \App\Entity\Territoire::class,
                'choice_label' => 'nom',
                'empty_value' => 'Aucun, territoire indépendant',
                'empty_data' => null,
            ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\Territoire::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'territoire';
    }
}
