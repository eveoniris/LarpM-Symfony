<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\GuildeController.
 */
class GuildeController extends AbstractController
{
    public function indexAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $repo = $entityManager->getRepository('\App\Entity\Guilde');
        $guildes = $repo->findAll();

        return $this->render('guilde/index.twig', ['guildes' => $guildes]);
    }

    public function addAction(Request $request,  EntityManagerInterface $entityManager)
    {
        if ('POST' === $request->getMethod()) {
            $label = $request->get('label');
            $description = $request->get('description');

            $guilde = new \App\Entity\Guilde();

            $guilde->setLabel($label);
            $guilde->setDescription($description);

            $entityManager->persist($guilde);
            $entityManager->flush();

            return $this->redirectToRoute('guilde_list');
        }

        return $this->render('guilde/add.twig');
    }

    public function modifyAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');
        $guilde = $entityManager->find('\App\Entity\Guilde', $id);
        if (!$guilde) {
            return $this->redirectToRoute('guilde_list');
        }

        if ('POST' === $request->getMethod()) {
            $label = $request->get('label');
            $description = $request->get('description');

            $guilde->setLabel($label);
            $guilde->setDescription($description);

            $entityManager->flush();

            return $this->redirectToRoute('guilde_list');
        }

        $repo = $entityManager->getRepository('\App\Entity\Guilde');

        return $this->render('guilde/modify.twig', ['guilde' => $guilde]);
    }

    public function removeAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $guilde = $entityManager->find('\App\Entity\Guilde', $id);

        if ($guilde) {
            if ('POST' === $request->getMethod()) {
                $entityManager->remove($guilde);
                $entityManager->flush();

                return $this->redirectToRoute('guilde_list');
            }

            return $this->render('chronologie/remove.twig', ['guilde' => $guilde]);
        } else {
            return $this->redirectToRoute('guilde_list');
        }
    }

    public function detailAction(Request $request,  EntityManagerInterface $entityManager)
    {
        $id = $request->get('index');

        $guilde = $entityManager->find('\App\Entity\Guilde', $id);

        if ($guilde) {
            return $this->render('guilde/detail.twig', ['guilde', $guilde]);
        } else {
            return $this->redirectToRoute('guilde_list');
        }
    }
}
