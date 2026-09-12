<?php

declare(strict_types=1);

namespace App\Form\Participant;

use App\Entity\Genre;
use App\Entity\Participant;
use App\Entity\PersonnageSecondaire;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Participant>
 */
class ParticipantPersonnageSecondaireType extends AbstractType
{
    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // L'archétype n'a pas de genre propre : on l'accorde sur le personnage
        // principal de la participation.
        $genre = $options['genre'];

        $builder->add('personnageSecondaire', EntityType::class, [
            'label' => 'Choisissez votre archétype de secours.',
            'required' => true,
            'expanded' => true,
            'class' => PersonnageSecondaire::class,
            'choice_label' => static fn (PersonnageSecondaire $archetype): string => $archetype->getLabelPourGenre($genre),
        ]);
    }

    /**
     * Définition de la classe d'entité concernée.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
            'genre' => null,
        ]);

        $resolver->setAllowedTypes('genre', ['null', Genre::class]);
    }

    /*
     * Nom du formlaire.
     */
}
