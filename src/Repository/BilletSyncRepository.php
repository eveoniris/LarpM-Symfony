<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\BilletSync;
use App\Entity\Gn;
use App\Enum\SyncEtat;

class BilletSyncRepository extends BaseRepository
{
    public function findOneByAttendee(Gn $gn, string $attendeeId): ?BilletSync
    {
        $sync = $this->findOneBy(['gn' => $gn, 'attendeeId' => $attendeeId]);

        return $sync instanceof BilletSync ? $sync : null;
    }

    /** @return list<BilletSync> */
    public function findByGnAndEtat(Gn $gn, ?SyncEtat $etat = null): array
    {
        $criteria = ['gn' => $gn];
        if (null !== $etat) {
            $criteria['etat'] = $etat;
        }

        $rows = $this->findBy($criteria, ['email' => 'ASC']);

        return array_values(array_filter($rows, static fn (object $row): bool => $row instanceof BilletSync));
    }
}
