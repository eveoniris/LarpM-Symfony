<?php

namespace App\Controller;

use App\Entity\EtatCivil;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * LarpManager\Controllers\EtatCivilController.
 *
 * @author kevin
 */
class EtatCivilController extends AbstractController
{
    /**
     * Affiche l'état civil de l'utilisateur.
     */
    #[Route('/etat-civil/{id}/detail', name: 'etatCivil.detail')]
    public function detailAction(Request $request, #[MapEntity] EtatCivil $etatCivil): Response
    {
        return $this->render(
            'etatCivil/detail.twig',
            ['etatCivil' => $etatCivil]
        );
    }
}
