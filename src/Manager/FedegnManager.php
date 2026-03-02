<?php

declare(strict_types=1);

namespace App\Manager;

use App\Entity\EtatCivil;
use DateTime;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

final class FedegnManager
{
    private ContainerBagInterface $params;

    public function __construct(ContainerBagInterface $params)
    {
        $this->params = $params;
    }

    /**
     * Suppression des accents (pour construire le cleanname).
     */
    private static function remove_accents(string $str, string $charset = 'utf-8'): string
    {
        $str = htmlentities($str, \ENT_NOQUOTES, $charset);

        $str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
        // supprime les autres caractères

        return preg_replace('#&[^;]+;#', '', $str);
    }

    /**
     * Supression du BOM en début de chaine.
     */
    private static function remove_utf8_bom(string $text): ?string
    {
        $bom = pack('H*', 'EFBBBF');

        return preg_replace("/^{$bom}/", '', $text);
    }

    /**
     * Fourni le cleanname utilisé par la fédégn.
     */
    public static function cleanname(string $prenom, string $nom): string
    {
        return strtolower(self::remove_accents($prenom . $nom));
    }

    /**
     * Test si l'utilisateur dispose d'un pass GN.
     */
    public function test(EtatCivil $etatCivil): string|false
    {
        $url = $this->params->get('fedegn.url');
        $password = $this->params->get('fedegn.password');
        $cleanname = $this->cleanname($etatCivil->getPrenom(), $etatCivil->getNom());
        $birthdate = $etatCivil->getDateNaissance()->format('Y-m-d');
        $year = new DateTime('NOW');
        $year = $year->format('Y');

        $file_content = @file_get_contents($url . '?password=' . $password . '&cleanname=' . $cleanname . '&birthdate=' . $birthdate . '&year=' . $year);
        if (false === $file_content) {
            return false;
        }

        $result = $this->remove_utf8_bom($file_content);
        if (0 === strcmp($result, 'false')) {
            return false;
        }

        return $result;
    }
}
