<?php

namespace App\Form\Territoire;

use App\Entity\Appelation;
use App\Entity\Langue;
use App\Entity\Merveille;
use App\Entity\Religion;
use App\Entity\Ressource;
use App\Entity\Territoire;
use App\Enum\TerritoireStatut;
use App\Repository\LangueRepository;
use App\Repository\ReligionRepository;
use App\Repository\RessourceRepository;
use App\Repository\TerritoireRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
            /*->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'required' => false,
                'choices' => ['Normal' => 'Normal', 'Instable' => 'Instable'],
            ])/
        */
            ->add('geojson', TextareaType::class, [
                'label' => 'GeoJSON',
                'required' => false,
                'attr' => ['rows' => 10],
            ])
            ->add('color', TextType::class, [
                'label' => 'Couleur',
                'required' => false,
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
                'query_builder' => static function (RessourceRepository $rr) {
                    return $rr->createQueryBuilder('rr')->orderBy('rr.label', 'ASC');
                },
            ])
            ->add('exportations', EntityType::class, [
                'required' => false,
                'label' => 'Exportations',
                'class' => Ressource::class,
                'multiple' => true,
                'expanded' => true,
                'mapped' => true,
                'choice_label' => 'label',
                'query_builder' => static function (RessourceRepository $rr) {
                    return $rr->createQueryBuilder('rr')->orderBy('rr.label', 'ASC');
                },
            ])
            ->add('languePrincipale', EntityType::class, [
                'required' => false,
                'label' => 'Langue principale',
                'class' => Langue::class,
                'multiple' => false,
                'mapped' => true,
                'choice_label' => 'label',
                'query_builder' => static function (LangueRepository $lr) {
                    return $lr->createQueryBuilder('lr')->orderBy('lr.label', 'ASC');
                },
            ])
            ->add('langues', EntityType::class, [
                'required' => false,
                'label' => 'Langues parlées (selectionnez aussi la langue principale)',
                'class' => Langue::class,
                'multiple' => true,
                'expanded' => true,
                'mapped' => true,
                'choice_label' => 'label',
                'query_builder' => static function (LangueRepository $lr) {
                    return $lr->createQueryBuilder('lr')->orderBy('lr.label', 'ASC');
                },
            ])
            ->add('religionPrincipale', EntityType::class, [
                'required' => false,
                'label' => 'Religion dominante',
                'class' => Religion::class,
                'multiple' => false,
                'mapped' => true,
                'choice_label' => 'label',
                'query_builder' => static function (ReligionRepository $rr) {
                    return $rr->createQueryBuilder('rr')->orderBy('rr.label', 'ASC');
                },
            ])
            ->add('religions', EntityType::class, [
                'required' => false,
                'label' => 'Religions secondaires',
                'class' => Religion::class,
                'multiple' => true,
                'expanded' => true,
                'mapped' => true,
                'choice_label' => 'label',
                'query_builder' => static function (ReligionRepository $rr) {
                    return $rr->createQueryBuilder('rr')->orderBy('rr.label', 'ASC');
                },
            ])
            ->add('territoire', EntityType::class, [
                'required' => false,
                'label' => 'Ce territoire dépend de ',
                'class' => Territoire::class,
                'choice_label' => 'nom',
                // 'empty_data' => 'Aucun, territoire indépendant',
                'empty_data' => null,
                'mapped' => true,
                'query_builder' => static function (TerritoireRepository $tr) {
                    return $tr->createQueryBuilder('tr')->orderBy('tr.nom', 'ASC');
                },
            ]);
        /* Merveille are added from MerveilleForm
        ->add('merveilles', EntityType::class, [
            'required' => false,
            'label' => 'Merveille',
            'class' => Merveille::class,
            'multiple' => true,
            'expanded' => true,
            'mapped' => true,
            'choice_label' => 'label',
        ])*/
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
