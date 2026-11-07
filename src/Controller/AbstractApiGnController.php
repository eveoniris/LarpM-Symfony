<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Gn;
use App\Repository\ApiClientRepository;
use App\Repository\GnRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base des contrôleurs /api/admin : résolution du GN visé.
 */
abstract class AbstractApiGnController extends AbstractController
{
    /**
     * Résout le GN visé (ou une réponse d'erreur). ROLE_API_READ : GN de l'application ou GN actif par défaut ; ROLE_API_ADMIN : ?gn=<id>, ?gn=all (null) ou GN actif par défaut.
     */
    protected function resolveGn(Request $request, ApiClientRepository $clients, GnRepository $gns): Gn|JsonResponse|null
    {
        $gnParam = $request->query->get('gn');
        $gnParam = \is_string($gnParam) && '' !== $gnParam ? $gnParam : null;

        if (!$this->isGranted('ROLE_API_ADMIN')) {
            $client = $clients->findOneByUser($this->getUser());
            if (null === $client || !$client->isActive()) {
                return $this->json(['error' => 'Accès refusé : application inconnue ou révoquée'], 403);
            }
            $gn = $client->getGn() ?? $gns->findFromFilter(null);
            if (null !== $gnParam && (null === $gn || $gnParam !== (string) $gn->getId())) {
                return $this->json(['error' => 'Accès refusé : GN hors du périmètre de l\'application'], 403);
            }

            return $gn;
        }

        $gn = $gns->findFromFilter($gnParam);
        if ('all' !== $gnParam && null !== $gnParam && ctype_digit($gnParam) && null === $gn) {
            return $this->json(['error' => 'GN introuvable'], 404);
        }

        return $gn;
    }
}
