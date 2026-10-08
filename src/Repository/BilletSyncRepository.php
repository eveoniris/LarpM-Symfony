<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\BilletSync;
use App\Entity\Gn;
use App\Enum\SyncEtat;

/**
 * @extends BaseRepository<BilletSync>
 */
class BilletSyncRepository extends BaseRepository
{
    public function findOneByAttendee(Gn $gn, string $attendeeId): ?BilletSync
    {
        return $this->findOneBy(['gn' => $gn, 'attendeeId' => $attendeeId]);
    }

    /** @return list<BilletSync> */
    public function findByGnAndEtat(Gn $gn, ?SyncEtat $etat = null): array
    {
        $criteria = ['gn' => $gn];
        if (null !== $etat) {
            $criteria['etat'] = $etat;
        }

        return $this->findBy($criteria, ['email' => 'ASC']);
    }
}
