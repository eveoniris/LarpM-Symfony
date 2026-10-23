<?php

declare(strict_types=1);

namespace App\Form\Depouillement;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Création du ou des héritiers d'une conception : le premier est obligatoire, le second (jumeaux) est laissé vide sinon.
 */
class HeritiersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('premier', HeritierType::class, ['label' => 'Premier héritier'])
            ->add('second', HeritierType::class, ['label' => 'Second héritier (jumeaux)', 'obligatoire' => false])
            ->add('submit', SubmitType::class, ['label' => 'Créer le ou les héritiers']);
    }
}
