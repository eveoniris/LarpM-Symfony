<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Participant;
use App\Enum\Role;
use App\Security\MultiRolesExpression;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ParticipantType extends AbstractType
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    /**
     * Construction du formulaire.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('couchage', ChoiceType::class, [
            'label' => 'Type de couchage',
            'choices' => [
                "Sur votre camps en tente RP, qu'elle soit définie en jeu ou non" => 'RP',
                'Dans le champ HRP hors du jeu' => 'HRP',
                "Hors de l'enceinte du site de jeu" => 'HSJ',
            ],
            'required' => true,
        ]);

        if (!$this->security->isGranted(new MultiRolesExpression(Role::ADMIN))) {
            return;
        }

        $builder->add('special', TextType::class, [
            'label' => 'Spécial: Informations complémentaires (animaux, ...)',
            'required' => false,
        ]);
    }

    /**
     * Définition de l'entité concerné.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Participant::class,
        ]);
    }

    /**
     * Nom du formulaire.
     */
}
