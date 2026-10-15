<?php

declare(strict_types=1);

namespace App\Form\Personnage;

use App\Entity\PersonnagesReligions;
use App\Entity\ReligionLevel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class PersonnageReligionType extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('religionLevel', EntityType::class, [
            'required' => true,
            'label' => 'Votre degré de fanatisme',
            'class' => ReligionLevel::class,
            'choice_label' => 'label',
            'choice_attr' => static fn (ReligionLevel $level): array => ['data-niveau' => $level->getLabel()],
            'placeholder' => '— Choisir un degré —',
            'attr' => ['data-confirm-choice-target' => 'select', 'data-confirm-choice-name' => 'level'],
            'constraints' => [new NotBlank(message: 'Veuillez choisir un degré de fanatisme.')],
        ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PersonnagesReligions::class,
        ]);
    }

    /*
     * Nom du formulaire.
     */
}
