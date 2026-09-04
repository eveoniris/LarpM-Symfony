<?php

declare(strict_types=1);

namespace App\Form\Participant;

use App\Entity\Participant;
use App\Entity\Personnage;
use App\Enum\PersonnageRoleType;
use App\Repository\ParticipantRepository;
use InvalidArgumentException;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Choix d'un personnage alternatif (relève ou substitution) sur une participation.
 *
 * Les deux rôles partagent exactement les mêmes règles de sélection : un personnage
 * vivant du joueur, qui ne tient aucun autre rôle sur ce GN - ni dans cette
 * participation, ni dans une autre.
 *
 * @extends AbstractType<Participant>
 */
class ParticipantPersonnageAlternatifType extends AbstractType
{
    /** Les seuls rôles éditables par ce formulaire. */
    private const ROLES = [
        PersonnageRoleType::RELEVE->value,
        PersonnageRoleType::SUBSTITUTION->value,
    ];

    public function __construct(
        private readonly ParticipantRepository $participantRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $role = $options['role'];
        $participant = $options['participant'];

        if (!$role instanceof PersonnageRoleType || !\in_array($role->value, self::ROLES, true)) {
            throw new InvalidArgumentException('Le rôle doit être RELEVE ou SUBSTITUTION.');
        }

        if (!$participant instanceof Participant) {
            throw new InvalidArgumentException('La participation est obligatoire.');
        }

        $builder->add($role->field(), EntityType::class, [
            'required' => false,
            'label' => sprintf('Choisissez votre %s.', lcfirst($role->label())),
            'multiple' => false,
            'expanded' => true,
            'class' => Personnage::class,
            'choice_label' => 'identity',
            'placeholder' => 'Aucun',
            'empty_data' => null,
            'choices' => $this->choix($participant, $role->field()),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
            'role' => null,
            'participant' => null,
        ]);

        $resolver->setAllowedTypes('role', [PersonnageRoleType::class]);
        $resolver->setAllowedTypes('participant', [Participant::class]);
    }

    /**
     * Personnages proposables pour ce rôle.
     *
     * @return array<int, Personnage>
     */
    private function choix(Participant $participant, string $champCourant): array
    {
        // Déjà engagés sur ce GN par une AUTRE participation.
        $indisponibles = array_flip($this->participantRepository->findPersonnageIdsEngagesSurGn($participant->getGn(), $participant));

        // Déjà engagés par cette participation dans un AUTRE rôle. Le rôle en cours
        // d'édition reste sélectionnable, sinon sa valeur actuelle disparaîtrait de
        // la liste.
        foreach ($participant->getPersonnagesEngages() as $role => $personnage) {
            if (PersonnageRoleType::from($role)->field() === $champCourant) {
                continue;
            }

            $indisponibles[$personnage->getId()] = true;
        }

        $choix = [];
        foreach ($participant->getUser()?->getPersonnagesAvailableToParticipation() ?? [] as $personnage) {
            if (isset($indisponibles[$personnage->getId()])) {
                continue;
            }

            $choix[] = $personnage;
        }

        return $choix;
    }
}
