<?php

declare(strict_types=1);

namespace App\Form\Depouillement;

use App\Entity\Ingredient;
use App\Entity\Potion;
use App\Entity\Priere;
use App\Entity\Ressource;
use App\Entity\Sort;
use App\Enum\ActionFinOpus;
use App\Service\ContenuJoueur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
    /** Tout le formulaire. */
    public const string PORTION_TOUT = 'tout';

    /** Étape « Contenu » : pièces jointes, ressources trouvées, apprentissages. */
    public const string PORTION_CONTENU = 'contenu';

    /** Étape « Actions de fin d'Opus ». */
    public const string PORTION_ACTIONS = 'actions';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $portion = $options['portion'];

        if (self::PORTION_ACTIONS !== $portion) {
            $this->ajouterPiecesJointes($builder);
            $this->ajouterApprentissages($builder);
            $builder
                ->add('personnageMort', CheckboxType::class, [
                    'label' => 'Le personnage est mort pendant cet opus',
                    'required' => false,
                    'help' => 'Information pour le scénariste : la mort n\'est pas appliquée automatiquement à la fiche.',
                ])
                ->add('circonstancesMort', TextareaType::class, [
                    'label' => 'Circonstances de la mort (facultatif)',
                    'required' => false,
                    'attr' => ['rows' => 2],
                ])
                ->add('surplus', CompteursEnveloppeType::class);
        }

        if (self::PORTION_CONTENU !== $portion) {
            $this->ajouterActions($builder);
        }

        $builder->add('submit', SubmitType::class, ['label' => self::PORTION_ACTIONS === $portion ? 'Enregistrer les actions' : 'Enregistrer le contenu']);
    }

    /**
     * Le formulaire d'une étape ne modifie que ses propres champs : il reçoit le contenu déjà enregistré.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ContenuJoueur::class, 'portion' => self::PORTION_TOUT]);
        $resolver->setAllowedValues('portion', [self::PORTION_TOUT, self::PORTION_CONTENU, self::PORTION_ACTIONS]);
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
            // Plusieurs niveaux d'un même domaine portent le même nom : on affiche domaine et niveau pour les distinguer.
            ->add('sorts', EntityType::class, [
                ...$this->liste(Sort::class, 'Sorts appris pendant le jeu'),
                'choice_label' => static fn (Sort $sort): string => \sprintf('%s - %s (niveau %d)', $sort->getDomaine()->getLabel(), $sort->getLabel(), $sort->getNiveau()),
            ])
            ->add('prieres', EntityType::class, [
                ...$this->liste(Priere::class, 'Prières apprises pendant le jeu'),
                'choice_label' => static fn (Priere $priere): string => \sprintf('%s - %s (niveau %d)', $priere->getSphere()?->getLabel() ?? 'Sans sphère', $priere->getLabel(), $priere->getNiveau()),
            ])
            ->add('potions', EntityType::class, [
                ...$this->liste(Potion::class, 'Potions (recettes) apprises pendant le jeu'),
                'choice_label' => static fn (Potion $potion): string => \sprintf('%s - %s (niveau %d)', $potion->getNumero(), $potion->getLabel(), $potion->getNiveau()),
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
        // Un seul choix par action, pré-sélectionné d'après les ressources trouvées : aucune, validée ou souhaitée sans assez de ressources.
        foreach ([
            'fruits' => ActionFinOpus::FRUITS_LEGUMES,
            'soie' => ActionFinOpus::SOIE,
            'gemme' => ActionFinOpus::GEMME,
            'sel' => ActionFinOpus::SEL,
            'fourrure' => ActionFinOpus::FOURRURE,
            'ivoire' => ActionFinOpus::IVOIRE,
        ] as $champ => $action) {
            $builder->add($champ . 'Decision', ChoiceType::class, [
                'label' => false,
                'expanded' => true,
                'choices' => [
                    'Aucune action : ne rien faire' => ContenuJoueur::DECISION_AUCUNE,
                    \sprintf('Action validée : consomme %d %s', $action->quantiteRessource(), mb_strtolower($action->ressource())) => ContenuJoueur::DECISION_VALIDEE,
                    'Le joueur a souhaité l\'action mais n\'a pas assez de ressources (sans effet, non bloquant)' => ContenuJoueur::DECISION_INSUFFISANT,
                ],
            ]);
        }

        $builder
            ->add('gemmePieces', IntegerType::class, [
                'label' => 'Pièces d\'or conservées (3 maximum)',
                'required' => false,
                'empty_data' => '0',
                'constraints' => [new Range(min: 0, max: 3, notInRangeMessage: 'Entre 0 et 3 pièces d\'or.')],
                'attr' => ['inputmode' => 'numeric', 'min' => 0, 'max' => 3],
            ])
            // La fiche demande le nom et la couleur de chaque ingrédient.
            ->add('selIngredients', EntityType::class, [
                ...$this->liste(Ingredient::class, 'Ingrédients conservés (6 maximum)', 6),
                'choice_label' => static fn (Ingredient $ingredient): string => $ingredient->getLabel() . ' - ' . $ingredient->getColor(),
            ])
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
