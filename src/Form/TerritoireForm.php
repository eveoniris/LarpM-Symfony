<?php


namespace App\Form;

use App\Entity\Appelation;
use App\Entity\Langue;
use App\Entity\Religion;
use App\Entity\Ressource;
use App\Entity\Territoire;
use App\Enum\TerritoireStatut;
use JetBrains\PhpStorm\Deprecated;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[Deprecated]
/** @see \App\Form\Territoire\TerritoireForm */
class TerritoireForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('nom', TextType::class, [
            'label' => 'Nom',
            'required' => true,
        ])
            ->add('appelation', EntityType::class, [
                'label' => "Choisissez l'appelation de ce territoire",
                'required' => true,
                'class' => Appelation::class,
                'multiple' => false,
                'mapped' => true,
                'choice_label' => 'label',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 10,
                ],
            ])
            ->add('description_secrete', TextareaType::class, [
                'label' => 'Description connue des habitants',
                'required' => false,
                'attr' => [
                    'class' => 'tinymce',
                    'rows' => 10,
                ],
            ])
            ->add('statut', EnumType::class, [
                'label' => 'Statut',
                'required' => false,
                'class' => TerritoireStatut::class,
            ])
            ->add('geojson', TextareaType::class, [
                'label' => 'GeoJSON',
                'required' => false,
                'attr' => ['rows' => 10],
            ])
            ->add('capitale', TextType::class, [
                'label' => 'Capitale',
                'required' => false,
            ])
            ->add('politique', TextType::class, [
                'label' => 'Système politique',
                'required' => false,
            ])
            ->add('dirigeant', TextType::class, [
                'label' => 'Dirigeant',
                'required' => false,
            ])
            ->add('population', TextType::class, [
                'label' => 'Population',
                'required' => false,
            ])
            ->add('symbole', TextType::class, [
                'label' => 'Symbole',
                'required' => false,
            ])
            ->add('tech_level', TextType::class, [
                'label' => 'Niveau technologique',
                'required' => false,
            ])
            ->add('type_racial', TextareaType::class, [
                'label' => 'Type racial',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('inspiration', TextareaType::class, [
                'label' => 'Inspiration',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('armes_predilection', TextareaType::class, [
                'label' => 'Armes de prédilection',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('vetements', TextareaType::class, [
                'label' => 'Vetements',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('noms_masculin', TextareaType::class, [
                'label' => 'Noms masculins',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('noms_feminin', TextareaType::class, [
                'label' => 'Noms féminins',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('frontieres', TextareaType::class, [
                'label' => 'Frontières',
                'required' => false,
                'attr' => ['rows' => 5],
            ])
            ->add('importations', EntityType::class, [
                'required' => false,
                'label' => 'Importations',
                'class' => Ressource::class,
                'multiple' => true,
                'expanded' => true,
                'mapped' => true,
                'choice_label' => 'label',
            ])
            ->add('exportations', EntityType::class, [
                'required' => false,
                'label' => 'Exportations',
                'class' => Ressource::class,
                'multiple' => true,
                'expanded' => true,
                'mapped' => true,
                'choice_label' => 'label',
            ])
            ->add('languePrincipale', EntityType::class, [
                'required' => false,
                'label' => 'Langue principale',
                'class' => Langue::class,
                'multiple' => false,
                'mapped' => true,
                'choice_label' => 'label',
            ])
            ->add('langues', EntityType::class, [
                'required' => false,
                'label' => 'Langues parlées (selectionnez aussi la langue principale)',
                'class' => Langue::class,
                'multiple' => true,
                'expanded' => true,
                'mapped' => true,
                'choice_label' => 'label',
            ])
            ->add('religionPrincipale', EntityType::class, [
                'required' => false,
                'label' => 'Religion dominante',
                'class' => Religion::class,
                'multiple' => false,
                'mapped' => true,
                'choice_label' => 'label',
            ])
            ->add('religions', EntityType::class, [
                'required' => false,
                'label' => 'Religions secondaires',
                'class' => Religion::class,
                'multiple' => true,
                'expanded' => true,
                'mapped' => true,
                'choice_label' => 'label',
            ])
            ->add('territoire', EntityType::class, [
                'required' => false,
                'label' => 'Ce territoire dépend de ',
                'class' => Territoire::class,
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
            'data_class' => Territoire::class,
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
        return 'territoire';
    }
}
