<?php

declare(strict_types=1);

namespace App\Form\Depouillement;

use App\Service\DeclarationJoueur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Ce que le joueur déclare de son retour de jeu en texte libre (les responsables le lisent et le traitent), en plus de ce qu'il a appris.
 */
class DeclarationJoueurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sortiDuJeu', CheckboxType::class, [
                'label' => 'Mon personnage est sorti du jeu',
                'required' => false,
            ])
            ->add('sortiDetails', TextareaType::class, $this->texte('Détails de la sortie du jeu', 2));

        $textes = [
            'eleves' => ['Enseignements donnés', 'Si vous êtes au moins expert dans une compétence : à qui avez-vous enseigné, quoi (numéros de personnage des élèves) ?'],
            'constructions' => ['Constructions', 'Artisan ou expert, vous avez participé à une ou plusieurs constructions : lesquelles, avec quel groupe ?'],
            'renommee' => ['Gains et pertes de renommée', 'Ce qui a fait gagner ou perdre de la renommée à votre personnage ou à un autre, avec les numéros de personnage concernés.'],
            'precisions' => ['Précisions diverses', 'Maladies, folies, et tout ce qui n\'entre pas dans les autres rubriques.'],
            'autres' => ['Autres informations', null],
        ];

        foreach ($textes as $nom => [$libelle, $aide]) {
            $builder->add($nom, TextareaType::class, $this->texte($libelle, 4, $aide));
            if ('renommee' === $nom) {
                $builder->add('renommeePoints', IntegerType::class, [
                    'label' => 'Renommée de mon personnage (points)',
                    'required' => false,
                    'help' => 'Total gagné (positif) ou perdu (négatif) pendant l\'opus. Appliqué à la validation, une fois confirmé par un organisateur.',
                    'constraints' => [new Range(min: -20, max: 20)],
                ]);
            }
        }

        $builder->add('submit', SubmitType::class, ['label' => 'Enregistrer mon retour']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => DeclarationJoueur::class]);
    }

    /**
     * @return array<string, mixed>
     */
    private function texte(string $libelle, int $lignes, ?string $aide = null): array
    {
        return [
            'label' => $libelle,
            'required' => false,
            'help' => $aide,
            'attr' => ['rows' => $lignes],
            'constraints' => [new Length(max: 5000)],
        ];
    }
}
