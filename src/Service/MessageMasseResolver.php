<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\PersonnageRepository;
use App\Repository\UserRepository;

/**
 * Transforme une liste d'identifiants utilisateur ou personnage en joueurs à prévenir (un par joueur).
 */
final readonly class MessageMasseResolver
{
    public const string TYPE_USER = 'user';
    public const string TYPE_PERSONNAGE = 'personnage';

    public function __construct(
        private UserRepository $userRepository,
        private PersonnageRepository $personnageRepository,
    ) {
    }

    /** @param list<int> $ids */
    public function resolve(string $type, array $ids): MessageMasseCibles
    {
        return self::TYPE_PERSONNAGE === $type ? $this->resolvePersonnages($ids) : $this->resolveUsers($ids);
    }

    /** @param list<int> $ids */
    private function resolveUsers(array $ids): MessageMasseCibles
    {
        $destinataires = [];
        foreach ($ids as $id) {
            $destinataires[$id] = null;
        }
        foreach ($this->userRepository->findBy(['id' => $ids]) as $user) {
            $destinataires[$user->getId()] = new MessageMasseDestinataire($user);
        }

        return new MessageMasseCibles(array_filter($destinataires), [], array_keys(array_filter($destinataires, static fn (?MessageMasseDestinataire $d): bool => null === $d)));
    }

    /** @param list<int> $ids */
    private function resolvePersonnages(array $ids): MessageMasseCibles
    {
        $trouves = [];
        foreach ($this->personnageRepository->findByIds($ids) as $personnage) {
            $trouves[$personnage->getId()] = $personnage;
        }

        $parUser = [];
        $sansUser = [];
        $introuvables = [];
        foreach ($ids as $id) {
            $personnage = $trouves[$id] ?? null;
            if (null === $personnage) {
                $introuvables[] = $id;
                continue;
            }
            $user = $personnage->getUser();
            if (null === $user) {
                $sansUser[] = $id;
                continue;
            }
            $parUser[$user->getId()]['user'] = $user;
            $parUser[$user->getId()]['personnages'][] = $personnage;
        }

        return new MessageMasseCibles(array_map(static fn (array $d): MessageMasseDestinataire => new MessageMasseDestinataire($d['user'], $d['personnages']), $parUser), $sansUser, $introuvables);
    }
}
