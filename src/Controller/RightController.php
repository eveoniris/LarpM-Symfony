<?php


namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * LarpManager\Controllers\RightController.
 *
 * @author kevin
 */
class RightController extends AbstractController
{
    /**
     * Liste des droits.
     *
     * @return View $view
     * TODO : Move to admin dashboard
     */
    #[Route('/rights/list', name: 'right.admin.list')]
    public function listAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $rights = $app['larp.manager']->getAvailableRoles();

        return $this->render('admin/right/list.twig', [
            'rights' => $rights]);
    }
}
