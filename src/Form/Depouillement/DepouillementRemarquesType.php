<?php

declare(strict_types=1);

namespace App\Form\Depouillement;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

class DepouillementRemarquesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('remarques', TextareaType::class, [
                'label' => 'Remarques',
                'required' => false,
                'help' => 'Écart avec la fiche papier, ressources refusées faute de moyens, objets ou documents laissés dans l\'enveloppe, élément introuvable à signaler à un orga...',
                'attr' => ['rows' => 8],
            ])
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer et continuer']);
    }
}
