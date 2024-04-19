<?php

namespace App\Form\Debriefing;

use App\Form\ListFindForm;
use JetBrains\PhpStorm\Deprecated;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[deprecated]
class DebriefingFindForm extends ListFindForm
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add('type', \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class, [
                'required' => true,
                'choices' => [
                    'Groupe' => 'Groupe',
                    'Auteur' => 'Auteur',
                    'Scénariste' => 'Scenariste',
                ],
                'label' => 'Type',
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
        return 'debriefingFind';
    }
}
