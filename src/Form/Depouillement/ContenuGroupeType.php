<?php

declare(strict_types=1);

namespace App\Form\Depouillement;

use App\Service\ContenuGroupe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Contenu constaté dans une enveloppe de groupe, d'après la fiche "Instructions Retour de jeu GROUPE".
 */
class ContenuGroupeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $materiel = $builder->create('materiel', FormType::class, ['label' => false]);
        foreach (ContenuGroupe::MATERIEL as $cle => $libelle) {
            $materiel->add($cle, IntegerType::class, [
                'label' => $libelle,
                'required' => false,
                'empty_data' => '0',
                'constraints' => [new Range(min: 0, max: 999)],
                'attr' => ['inputmode' => 'numeric', 'min' => 0],
            ]);
        }

        $builder
            ->add('ficheRetour', CheckboxType::class, ['label' => 'Fiche de retour groupe remplie', 'required' => false])
            ->add($materiel)
            ->add('autreMateriel', TextareaType::class, [
                'label' => 'Autre matériel de jeu individuel',
                'required' => false,
                'help' => 'Tout ce que les joueurs n\'ont pas gardé dans leur enveloppe et qui n\'est pas dans la liste ci-dessus.',
                'attr' => ['rows' => 2],
            ])
            ->add('compteurs', CompteursEnveloppeType::class)
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer le contenu du groupe']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ContenuGroupe::class]);
    }
}
