<?php

namespace App\Form\Bonus;

use App\Entity\Bonus;
use App\Entity\Competence;
use App\Enum\BonusApplication;
use App\Enum\BonusPeriode;
use App\Enum\BonusType;
use App\Repository\CompetenceRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BonusForm extends AbstractType
{
    /**
     * Contruction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('titre', TextType::class, [
            'required' => true,
            'label' => 'Nom',
        ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description succinte',
                'help' => 'Sera afficher sur les fiches. Cela peut-être le descriptif de ce que fait la compétence',
            ])
            ->add('type', EnumType::class, [
                'required' => false,
                'class' => BonusType::class,
                'label' => 'Type de bonus',
            ])
            ->add('valeur', IntegerType::class, [
                'required' => false,
                'label' => 'Valeur du bonus',
                'help' => '1 pour: une compétence, une langue. 3 pour: trois XP, trois ressources',
            ])
            ->add('application', ChoiceType::class, [
                'required' => false,
                'choices' => [
                    'Aucun' => null,
                    ...BonusApplication::toArray(),
                ],
                'label' => "Domaine d'application du bonus",
                'help' => "Peu usuel, où devrait s'afficher ou être pris en compte le bonus",
            ])
            ->add('periode', EnumType::class, [
                'required' => false,
                'class' => BonusPeriode::class,
                'label' => "Période d'application du bonus",
                'help' => "Pour un bonus d'origine choisir NATIVE: Le bonus ne sera donné que si le personage est natif du pays du groupe de sa première participation",
            ])
            ->add('competence', EntityType::class, [
                'required' => false,
                'label' => 'Choisissez la compétence à donner pour type COMPETENCE',
                'multiple' => false,
                'autocomplete' => true,
                'expanded' => false,
                'class' => Competence::class,
                'query_builder' => static fn (CompetenceRepository $er) => $er->getQueryBuilderFindAllOrderedByLabel(),
                'choice_label' => 'label',
                'placeholder' => 'Aucune',
                'empty_data' => null,
            ])
            ->add('json_data', TextareaType::class, [
                'required' => false,
                'label' => 'Données fonctionnel (pour un dev)',
                'help' => "C'est en JSON, via une liste de mot clé et ID. LANGUE, INGREDIENT, GROUPE. Et un mot clé 'condition' pour un tableau de valeur AND. Regarder les bonus existants",
            ])->get('json_data')
            ->addModelTransformer(
                new CallbackTransformer(
                    static fn ($data) => json_encode($data, JSON_THROW_ON_ERROR),
                    static fn ($data) => json_decode($data, true, 512, JSON_THROW_ON_ERROR),
                ),
            );
    }

    /**
     * Définition de l'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Bonus::class,
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
        return 'bonus';
    }
}
