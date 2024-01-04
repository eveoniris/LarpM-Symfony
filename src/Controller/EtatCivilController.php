<?php


namespace App\Controller;

use App\Entity\EtatCivil;
use Symfony\Component\HttpFoundation\Request;
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
    #[Route('/etat-civil/detail', name: 'etatCivil.detail')]
    public function detailAction(Request $request, EtatCivil $etatCivil)
    {
        return $this->render('admin/etatCivil/detail.twig', [
            'etatCivil' => $etatCivil,
        ]);
    }
}
