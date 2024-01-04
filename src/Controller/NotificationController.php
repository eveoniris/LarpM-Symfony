<?php


namespace App\Controller;

use App\Entity\Notification;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * LarpManager\Controllers\NotificationController.
 *
 * @author kevin
 */
class NotificationController extends AbstractController
{
    /**
     * Supprime une notification.
     */
    public function removeAction( EntityManagerInterface $entityManager, Request $request, Notification $notification): bool
    {
        if ($notification->getUser() != $this->getUser()) {
            return false;
        }

        $entityManager->remove($notification);
        $entityManager->flush();

        return true;
    }

    /**
     * Fourni la liste des notifications de l'utilisateur courant
     * On en profite pour stocker ses informations de connection.
     */
    public function listAction( EntityManagerInterface $entityManager, Request $request): JsonResponse
    {
        $qb = $entityManager->createQueryBuilder();
        $qb->from(\App\Entity\Notification::class, 'n');
        $qb->select('n');
        $qb->join('n.User', 'u');
        $qb->where('u.id = :UserId');
        $qb->setParameter('UserId', $this->getUser()->getId());

        $notifications = $qb->getQuery()->getArrayResult();

        foreach ($notifications as $key => $value) {
            $value['url_delete'] = $app['url_generator']->generate('notification.remove', ['notification' => $value['id']]);
            $notifications[$key] = $value;
        }

        $this->getUser()->setLastConnectionDate(new \DateTime('NOW'));
        $entityManager->persist($this->getUser());
        $entityManager->flush();

        $lastConnected = $entityManager->getRepository(\App\Entity\User::class)->lastConnected();
        foreach ($lastConnected as $key => $value) {
            $value['url'] = $app['url_generator']->generate('User.view', ['id' => $value['id']]);
            $lastConnected[$key] = $value;
        }

        return new JsonResponse([
            'notifications' => $notifications,
            'lastConnected' => $lastConnected,
        ]);
    }
}
