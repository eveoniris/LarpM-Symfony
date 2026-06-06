<?php

declare(strict_types=1);

namespace App\Form\FicheRetourGroupe;

use App\Entity\FicheRetourGroupe;
use App\Entity\FicheRetourGroupeHistory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<mixed> */
class FicheRetourGroupeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = $options['is_new'];

        $builder->add('submitted_at', DateTimeType::class, [
            'label' => 'Date de soumission',
            'required' => false,
            'widget' => 'single_text',
        ]);

        $ressources = [
            'pieces_argent' => 'Pièces d\'argent',
            'pieces_or' => 'Pièces d\'or',
            'nb_ingredients' => 'Ingrédients (total)',
            'nb_potions' => 'Potions (total)',
            'armement' => 'Armement',
            'chevaux' => 'Chevaux',
            'fruits_legumes' => 'Fruits & Légumes',
            'matieres_simples' => 'Matières simples',
            'sel' => 'Sel',
            'betail' => 'Bétail',
            'coton' => 'Coton',
            'gemmes' => 'Gemmes',
            'moutons' => 'Moutons',
            'soie' => 'Soie',
            'bois' => 'Bois',
            'esclaves' => 'Esclaves',
            'ivoire' => 'Ivoire',
            'pierre' => 'Pierre',
            'teinture' => 'Teinture',
            'cereales' => 'Céréales',
            'fourrures' => 'Fourrures',
            'matieres_precieux' => 'Matières précieux',
            'poisson' => 'Poisson',
            'vin' => 'Vin',
        ];

        foreach ($ressources as $field => $label) {
            $builder->add($field, IntegerType::class, [
                'label' => $label,
                'required' => true,
                'attr' => ['min' => 0],
            ]);
        }

        $builder->add('commentaire', TextareaType::class, [
            'label' => 'Commentaire',
            'required' => false,
            'attr' => ['rows' => 4],
        ]);

        if (!$isNew) {
            $motifChoices = array_flip(FicheRetourGroupeHistory::MOTIF_TYPES);
            unset($motifChoices['Création'], $motifChoices['Import']);

            $builder->add('motif_type', ChoiceType::class, [
                'label' => 'Type de modification',
                'mapped' => false,
                'required' => true,
                'choices' => $motifChoices,
                'placeholder' => 'Choisir...',
            ])->add('motif', TextareaType::class, [
                'label' => 'Motif / Détail',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Ex: Consommation pour construction d\'un port à Sabeaa',
                ],
                'help' => 'Obligatoire pour les consommations.',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FicheRetourGroupe::class,
            'is_new' => false,
        ]);
    }
}
