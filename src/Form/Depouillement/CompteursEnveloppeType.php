<?php

declare(strict_types=1);

namespace App\Form\Depouillement;

use App\Service\CompteursEnveloppe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

/**
 * Les 24 cases du tableau de la fiche de retour de groupe (pièces, ingrédients, potions et ressources), sous forme de tableau
 * de quantités : sert au surplus d'une enveloppe de joueur et à l'enveloppe de groupe.
 */
class CompteursEnveloppeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach (CompteursEnveloppe::CHAMPS as $cle => $libelle) {
            $builder->add($cle, IntegerType::class, [
                'label' => $libelle,
                'required' => false,
                'empty_data' => '0',
                'constraints' => [new PositiveOrZero(message: 'La quantité ne peut pas être négative.')],
                'attr' => ['inputmode' => 'numeric', 'min' => 0],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null, 'label' => false]);
    }
}
