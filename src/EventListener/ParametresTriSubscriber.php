<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Les listes concatènent ?order_by= dans le DQL (orderBy('alias.' . $orderBy)). On n'accepte ici que des
 * noms de champs (« nom », « p.nom », liste « -nom,id » de OrderBy) : toute autre valeur est retirée et la
 * liste revient à son tri par défaut. order_dir est ramené à ASC ou DESC.
 */
final class ParametresTriSubscriber implements EventSubscriberInterface
{
    private const string CHAMP = '-?[A-Za-z_]\w*(\.[A-Za-z_]\w*)?';

    public static function getSubscribedEvents(): array
    {
        // Avant le pare-feu et les contrôleurs.
        return [KernelEvents::REQUEST => ['onRequest', 512]];
    }

    public function onRequest(RequestEvent $event): void
    {
        $query = $event->getRequest()->query;

        if ($query->has('order_by') && !self::estChampValide($query->all()['order_by'])) {
            $query->remove('order_by');
        }

        if ($query->has('order_dir')) {
            $sens = $query->all()['order_dir'];
            if (!\is_string($sens) || !\in_array(strtoupper($sens), ['ASC', 'DESC'], strict: true)) {
                $query->remove('order_dir');
            }
        }
    }

    public static function estChampValide(mixed $valeur): bool
    {
        return \is_string($valeur) && 1 === preg_match('/^' . self::CHAMP . '(,' . self::CHAMP . ')*$/', $valeur);
    }
}
