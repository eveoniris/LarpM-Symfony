<?php

namespace App\Form\Territoire;

use App\Entity\Bonus;
use App\Entity\OrigineBonus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TerritoireBonusForm extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('bonus', EntityType::class, [
            'required' => false,
            'label' => 'Bonus',
            'class' => Bonus::class,
            'multiple' => true,
            'autocomplete' => true,
            'expanded' => true,
            'mapped' => true,
            'label_html' => true,
            'choice_label' => static fn(Bonus $bonus, $currentKey) => '<strong>'.$bonus->getTitre(
                ).'</strong> - '.$bonus->getDescription(),
        ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
     
    }

    /**
     * Nom du formulaire.
     */
    public function getName(): string
    {
        return 'territoireBonus';
    }
}
