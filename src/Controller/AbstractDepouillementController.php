<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Depouillement;
use App\Entity\Gn;
use App\Entity\User;
use App\Enum\DepouillementEtape;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base des contrôleurs du dépouillement d'enveloppes : outils communs.
 *
 * Fonctionnalité en cours de développement : chaque contrôleur concret doit porter lui-même
 * #[IsGranted('ROLE_BETA_TEST')] (voir docs/plans/role-beta-test.md). Symfony n'hérite PAS cet attribut d'une classe parente :
 * placé ici il serait sans effet et laisserait les écrans ouverts (c'est ce que vérifie DepouillementAccesTest).
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
        if (!$utilisateur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $utilisateur;
    }

    /**
     * Contrôle le jeton CSRF d'un formulaire de l'écran. En cas d'échec, pose le message d'erreur et retourne false.
     */
    protected function jetonValide(Request $request, string $identifiant): bool
    {
        if ($this->isCsrfTokenValid($identifiant, $request->request->getString('_token'))) {
            return true;
        }

        $this->addFlash('error', 'Jeton de sécurité invalide.');

        return false;
    }

    /**
     * Affiche chaque erreur d'un formulaire invalide ou non soumis, en message d'erreur.
     */
    protected function flashErreurs(FormInterface $form): void
    {
        foreach ($form->getErrors(true) as $erreur) {
            $this->addFlash('error', $erreur instanceof FormError ? $erreur->getMessage() : 'Saisie invalide.');
        }
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
