<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Api\ApiClientManager;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Échange l'identifiant et le secret d'une application tierce contre un jeton JWT.
 */
final class ApiClientTokenController extends AbstractController
{
    #[Route('/api/client/token', name: 'api.client.token', methods: ['POST'])]
    #[OA\Tag(name: 'Authentification')]
    #[OA\Post(
        summary: 'Obtenir un jeton pour une application enregistrée',
        description: 'Échange client_id et client_secret contre un jeton JWT (valide 1 h). Aucun refresh token : l\'application refait l\'échange.',
        security: [],
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(required: ['client_id', 'client_secret'], properties: [
            new OA\Property(property: 'client_id', type: 'string', example: 'lm_0123456789abcdef'),
            new OA\Property(property: 'client_secret', type: 'string'),
        ]),
    )]
    #[OA\Response(response: 200, description: 'Jeton d\'accès', content: new OA\JsonContent(properties: [
        new OA\Property(property: 'token', type: 'string'),
    ]))]
    #[OA\Response(response: 401, description: 'Identifiants invalides ou application révoquée')]
    public function token(Request $request, ApiClientManager $manager, JWTTokenManagerInterface $jwt): JsonResponse
    {
        /** @var mixed $data */
        $data = json_decode($request->getContent(), true);
        $clientId = \is_array($data) && \is_string($data['client_id'] ?? null) ? $data['client_id'] : '';
        $secret = \is_array($data) && \is_string($data['client_secret'] ?? null) ? $data['client_secret'] : '';

        $client = $manager->authenticate($clientId, $secret);
        if (null === $client) {
            return new JsonResponse(['message' => 'Identifiants invalides'], 401);
        }

        return new JsonResponse(['token' => $jwt->create($client->getUser())]);
    }
}
