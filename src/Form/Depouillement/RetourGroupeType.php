<?php

declare(strict_types=1);

namespace App\Form\Depouillement;

use App\Service\RetourGroupe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Range;

/**
 * Retour de jeu d'un groupe : les numéros de personnage des six titres, les ordres de chaque phase et les précisions.
 */
class RetourGroupeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $titres = $builder->create('titres', FormType::class, ['label' => false]);
        foreach (RetourGroupe::TITRES as $cle => $libelle) {
            $titres->add($cle, IntegerType::class, [
                'label' => $libelle,
                'required' => false,
                'help' => 'Numéro du personnage (celui de son enveloppe).',
                'constraints' => [new Range(min: 1)],
                'attr' => ['inputmode' => 'numeric', 'min' => 1],
            ]);
        }

        $builder->add($titres);
        foreach ([1 => 'ordres1', 2 => 'ordres2', 3 => 'ordres3'] as $phase => $champ) {
            $builder->add($champ, TextareaType::class, [
                'label' => \sprintf('Ordres de la phase %d', $phase),
                'required' => false,
                'help' => 1 === $phase ? 'Ordres de conquête et autres ordres du groupe. Les phases suivantes sont facultatives.' : null,
                'attr' => ['rows' => 6],
                'constraints' => [new Length(max: 10_000)],
            ]);
        }

        $builder
            ->add('precisions', TextareaType::class, [
                'label' => 'Précisions',
                'required' => false,
                'attr' => ['rows' => 4],
                'constraints' => [new Length(max: 5000)],
            ])
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer le retour du groupe']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => RetourGroupe::class]);
    }
}
