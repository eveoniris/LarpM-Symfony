<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Bonus;
use App\Entity\Gn;
use App\Entity\Langue;
use App\Entity\Personnage;
use App\Enum\BonusType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Joueurs qui parlent une langue : langues enregistrées en base + langues issues des bonus
 * (origine, groupe, bonus personnels), calculées avec PersonnageService::getAllLanguesUnique().
 */
class LangueJoueursService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PersonnageService $personnageService,
        private readonly ConditionsService $conditionsService,
    ) {
    }

    /**
     * Personnages parlant la langue, avec la source qui prime. Avec un GN : personnages vivants inscrits à ce GN.
     *
     * @return array<int, string> source indexée par id de personnage
     */
    public function getSourcesParPersonnage(Langue $langue, ?Gn $gn = null): array
    {
        $result = [];

        $qb = $this->entityManager
            ->createQueryBuilder()
            ->select('IDENTITY(pl.personnage) AS pid', 'pl.source AS source')
            ->from('App\Entity\PersonnageLangues', 'pl')
            ->where('pl.langue = :langue')
            ->setParameter('langue', $langue->getId());

        if ($gn) {
            $qb
                ->innerJoin('pl.personnage', 'p')
                ->innerJoin('p.participants', 'pt')
                ->andWhere('pt.gn = :gn')
                ->andWhere('p.vivant = true')
                ->setParameter('gn', $gn->getId());
        }

        foreach ($qb->getQuery()->getArrayResult() as $row) {
            $result[(int) $row['pid']] = (string) $row['source'];
        }

        // Les langues de bonus ne sont pas en base : calcul exact, seulement si un bonus référence cette langue
        if (!$this->isReferencedByBonus($langue)) {
            return $result;
        }

        foreach ($this->getPersonnagesCandidats($gn) as $personnage) {
            $id = (int) $personnage->getId();
            if (array_key_exists($id, $result)) {
                continue;
            }

            foreach ($this->personnageService->getAllLanguesUnique($personnage) as $entry) {
                if ($entry['principal']->getLangue()?->getId() === $langue->getId()) {
                    $result[$id] = (string) $entry['principal']->getSource();
                    break;
                }
            }
        }

        return $result;
    }

    public function getQueryBuilder(Langue $langue, ?Gn $gn = null): QueryBuilder
    {
        $ids = array_keys($this->getSourcesParPersonnage($langue, $gn));

        return $this->entityManager
            ->getRepository(Personnage::class)
            ->createQueryBuilder('p')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', [] === $ids ? [0] : $ids);
    }

    /**
     * Nombre de personnages vivants du GN par langue (langues de bonus comprises).
     *
     * @return list<array{id: int|null, langue: string, secret: bool, total: int}>
     */
    public function getRepartitionGn(Gn $gn): array
    {
        $rows = [];

        foreach ($this->getPersonnagesCandidats($gn) as $personnage) {
            foreach ($this->personnageService->getAllLanguesUnique($personnage) as $entry) {
                $langue = $entry['principal']->getLangue();
                if (!$langue) {
                    continue;
                }

                $key = $langue->getId() ?? 'label:' . $langue->getLabel();
                $rows[$key] ??= [
                    'id' => $langue->getId(),
                    'langue' => $langue->getLabel(),
                    'secret' => $langue->getSecret(),
                    'total' => 0,
                ];
                ++$rows[$key]['total'];
            }
        }

        usort($rows, static fn (array $a, array $b): int => [$b['total'], $a['langue']] <=> [$a['total'], $b['langue']]);

        return array_values($rows);
    }

    /**
     * @return iterable<Personnage>
     */
    private function getPersonnagesCandidats(?Gn $gn): iterable
    {
        $qb = $this->entityManager->getRepository(Personnage::class)->createQueryBuilder('p')->distinct();

        $qb->innerJoin('p.participants', 'pt');
        if ($gn) {
            $qb
                ->where('pt.gn = :gn')
                ->andWhere('p.vivant = true')
                ->setParameter('gn', $gn->getId());
        }

        return $qb->getQuery()->toIterable();
    }

    private function isReferencedByBonus(Langue $langue): bool
    {
        /** @var list<Bonus> $bonuses */
        $bonuses = $this->entityManager->getRepository(Bonus::class)->findBy(['type' => BonusType::LANGUE->value]);

        foreach ($bonuses as $bonus) {
            foreach ($bonus->getDataAsList('langue') as $item) {
                if ((int) $this->conditionsService->getKeyValue('id', $item) === $langue->getId()) {
                    return true;
                }
            }
        }

        return false;
    }
}
