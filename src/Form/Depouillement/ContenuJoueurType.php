<?php

declare(strict_types=1);

namespace App\Form\Depouillement;

use App\Entity\Ingredient;
use App\Entity\Potion;
use App\Entity\Priere;
use App\Entity\Ressource;
use App\Entity\Sort;
use App\Service\ContenuJoueur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Contenu constaté dans une enveloppe de joueur, d'après la fiche "Instructions Retour de jeu JOUEUR".
 */
class ContenuJoueurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->ajouterPiecesJointes($builder);
        $this->ajouterApprentissages($builder);
        $this->ajouterActions($builder);

        $builder
            ->add('surplus', CompteursEnveloppeType::class)
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer le contenu']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ContenuJoueur::class]);
    }

    private function ajouterPiecesJointes(FormBuilderInterface $builder): void
    {
        foreach ([
            'fichePersonnage' => 'Fiche de personnage',
            'ficheRetour' => 'Fiche de retour joueur remplie',
            'ficheSort' => 'Fiche de sort',
            'fichePriere' => 'Fiche de prière',
            'carteAlchimiste' => 'Carte alchimiste ou herboriste',
            'copiesRecettesSorts' => 'Copies des recettes et des sorts appris',
        ] as $nom => $libelle) {
            $builder->add($nom, CheckboxType::class, ['label' => $libelle, 'required' => false]);
        }

        foreach ([
            'corruptionOuvertes' => 'Enveloppes de corruption ouvertes',
            'corruptionNonOuvertes' => 'Enveloppes de corruption non ouvertes',
            'pincesALinge' => 'Pinces à linge',
        ] as $nom => $libelle) {
            $builder->add($nom, IntegerType::class, [
                'label' => $libelle,
                'required' => false,
                'empty_data' => '0',
                'constraints' => [new Range(min: 0, max: 999)],
                'attr' => ['inputmode' => 'numeric', 'min' => 0],
            ]);
        }

        $builder->add('documentsNumerotes', TextType::class, [
            'label' => 'Documents de jeu officiels numérotés (numéros)',
            'required' => false,
            'help' => 'Les parchemins avec leur numéro, séparés par des virgules.',
        ]);
    }

    private function ajouterApprentissages(FormBuilderInterface $builder): void
    {
        $builder
            ->add('sorts', EntityType::class, $this->liste(Sort::class, 'Sorts appris pendant le jeu'))
            ->add('prieres', EntityType::class, $this->liste(Priere::class, 'Prières apprises pendant le jeu'))
            ->add('recettes', TextareaType::class, [
                'label' => 'Recettes apprises',
                'required' => false,
                'help' => 'Souvent une potion (recette d\'alchimie) : nom et façon de la réaliser. À vérifier par un orga.',
                'attr' => ['rows' => 3],
            ])
            ->add('introuvables', TextareaType::class, [
                'label' => 'Je ne trouve pas',
                'required' => false,
                'help' => 'Sort, prière, potion, ingrédient ou ressource absent des listes : décrivez-le ici, un orga sera sollicité et la remarque est ajoutée aux remarques du dépouillement.',
                'attr' => ['rows' => 2],
            ]);
    }

    private function ajouterActions(FormBuilderInterface $builder): void
    {
        foreach ([
            'fruits' => 'Fruits et légumes (5)',
            'soie' => 'Soie (3)',
            'gemme' => 'Gemme (1)',
            'sel' => 'Sel (1)',
            'fourrure' => 'Fourrure (1)',
            'ivoire' => 'Ivoire (1)',
        ] as $action => $ressource) {
            $builder
                ->add($action . 'Utilise', CheckboxType::class, ['label' => 'Action utilisée', 'required' => false])
                ->add($action . 'Jointes', CheckboxType::class, ['label' => 'Ressources jointes à l\'enveloppe : ' . $ressource, 'required' => false]);
        }

        $builder
            ->add('gemmePieces', IntegerType::class, [
                'label' => 'Pièces d\'or conservées (3 maximum)',
                'required' => false,
                'empty_data' => '0',
                'constraints' => [new Range(min: 0, max: 3, notInRangeMessage: 'Entre 0 et 3 pièces d\'or.')],
                'attr' => ['inputmode' => 'numeric', 'min' => 0, 'max' => 3],
            ])
            ->add('selIngredients', EntityType::class, $this->liste(Ingredient::class, 'Ingrédients conservés (6 maximum)', 6))
            ->add('fourrureRessources', EntityType::class, $this->liste(Ressource::class, 'Ressources conservées (3 maximum)', 3))
            ->add('ivoirePotions', EntityType::class, [
                ...$this->liste(Potion::class, 'Potions conservées (3 maximum, par numéro)', 3),
                'choice_label' => static fn (Potion $potion): string => $potion->getNumero() . ' - ' . $potion->getLabel(),
            ]);
    }

    /**
     * Liste à choix multiple avec auto-complétion (les choix sont rendus par le serveur, Tom Select filtre à la frappe).
     *
     * @param class-string $classe
     *
     * @return array<string, mixed>
     */
    private function liste(string $classe, string $libelle, ?int $maximum = null): array
    {
        return [
            'class' => $classe,
            'label' => $libelle,
            'choice_label' => 'label',
            'multiple' => true,
            'required' => false,
            'autocomplete' => true,
            'constraints' => null === $maximum ? [] : [new Count(max: $maximum, maxMessage: 'Au plus {{ limit }} éléments.')],
        ];
    }
}
