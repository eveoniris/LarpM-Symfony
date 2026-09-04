<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\Participant;
use App\Entity\Personnage;
use App\Enum\PersonnageRoleType;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ChainePersonnagesCoherenteValidator extends ConstraintValidator
{
    /** Rôle => chemin de propriété, pour rattacher la violation au bon champ de formulaire. */
    private const PROPERTY_PATHS = [
        PersonnageRoleType::PRINCIPAL->value => 'personnage',
        PersonnageRoleType::SUBSTITUTION->value => 'personnageSubstitution',
        PersonnageRoleType::RELEVE->value => 'personnageReleve',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ChainePersonnagesCoherente) {
            throw new UnexpectedValueException($constraint, ChainePersonnagesCoherente::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof Participant) {
            throw new UnexpectedValueException($value, Participant::class);
        }

        $engages = $value->getPersonnagesEngages();

        $this->validateRolesDistincts($engages, $constraint);
        $this->validateAppartenanceJoueur($value, $engages, $constraint);
        $this->validateNonEngageSurLeGn($value, $engages, $constraint);
    }

    /**
     * Un même personnage ne peut pas tenir deux rôles dans la même participation.
     *
     * @param array<string, Personnage> $engages
     */
    private function validateRolesDistincts(array $engages, ChainePersonnagesCoherente $constraint): void
    {
        $vus = [];

        foreach ($engages as $role => $personnage) {
            $id = $personnage->getId();

            if (isset($vus[$id])) {
                $this->context
                    ->buildViolation($constraint->messageDoublonInterne)
                    ->setParameter('{{ personnage }}', $personnage->getIdentity())
                    ->setParameter('{{ role1 }}', PersonnageRoleType::from($vus[$id])->label())
                    ->setParameter('{{ role2 }}', PersonnageRoleType::from($role)->label())
                    ->atPath(self::PROPERTY_PATHS[$role])
                    ->addViolation();

                continue;
            }

            $vus[$id] = $role;
        }
    }

    /**
     * On ne peut engager qu'un personnage de sa propre liste. Un personnage encore
     * orphelin (sans détenteur) reste éligible : il sera rattaché à la validation.
     *
     * @param array<string, Personnage> $engages
     */
    private function validateAppartenanceJoueur(
        Participant $participant,
        array $engages,
        ChainePersonnagesCoherente $constraint,
    ): void {
        $userId = $participant->getUser()?->getId();

        if (null === $userId) {
            return;
        }

        foreach ($engages as $role => $personnage) {
            $detenteur = $personnage->getUser();

            if (null === $detenteur || $detenteur->getId() === $userId) {
                continue;
            }

            $this->context
                ->buildViolation($constraint->messageAutreJoueur)
                ->setParameter('{{ personnage }}', $personnage->getIdentity())
                ->atPath(self::PROPERTY_PATHS[$role])
                ->addViolation();
        }
    }

    /**
     * Un personnage ne peut tenir qu'un seul rôle sur un GN, toutes participations
     * confondues.
     *
     * @param array<string, Personnage> $engages
     */
    private function validateNonEngageSurLeGn(
        Participant $participant,
        array $engages,
        ChainePersonnagesCoherente $constraint,
    ): void {
        if ([] === $engages) {
            return;
        }

        /** @var ParticipantRepository $repository */
        $repository = $this->entityManager->getRepository(Participant::class);
        $gn = $participant->getGn();

        foreach ($engages as $role => $personnage) {
            $autre = $repository->findParticipationEngageantPersonnage($gn, $personnage, $participant);

            if (null === $autre) {
                continue;
            }

            $this->context
                ->buildViolation($constraint->messageDejaEngage)
                ->setParameter('{{ personnage }}', $personnage->getIdentity())
                ->setParameter('{{ joueur }}', $autre->getUserFullName())
                ->atPath(self::PROPERTY_PATHS[$role])
                ->addViolation();
        }
    }
}
