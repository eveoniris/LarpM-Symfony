<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Depouillement;
use App\Entity\Gn;
use App\Entity\User;
use App\Enum\DepouillementEtape;

/**
 * Base des contrôleurs du dépouillement d'enveloppes : outils communs.
 */
abstract class AbstractDepouillementController extends AbstractController
{
    /**
     * Le dépouillement doit appartenir au GN de l'URL.
     */
    protected function verifier(Gn $gn, Depouillement $depouillement): void
    {
        if ($depouillement->getGn()->getId() !== $gn->getId()) {
            throw $this->createNotFoundException();
        }
    }

    protected function utilisateur(): User
    {
        $utilisateur = $this->getUser();
        \assert($utilisateur instanceof User, description: 'Utilisateur connecté attendu');

        return $utilisateur;
    }

    /**
     * @return array{gn: int|null, depouillement: int|null, etape: string}
     */
    protected function parametres(Depouillement $depouillement, DepouillementEtape $etape): array
    {
        return [
            'gn' => $depouillement->getGn()->getId(),
            'depouillement' => $depouillement->getId(),
            'etape' => $etape->value,
        ];
    }
}
