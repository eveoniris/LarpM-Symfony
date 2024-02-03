<?php

namespace App\Manager;

use App\Entity\EtatCivil;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

final class FedegnManager
{
	private $params;

	public function __construct(ContainerBagInterface $params) {
		$this->params = $params;
    }

    /**
	 * Suppression des accents (pour construire le cleanname)
	 */
	private static function remove_accents($str, $charset='utf-8')
	{
		$str = htmlentities($str, ENT_NOQUOTES, $charset);
	
		$str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
		$str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
		$str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères
	
		return $str;
	}
	
	/**
	 * Supression du BOM en début de chaine
	 */
	private static function remove_utf8_bom($text)
	{
		$bom = pack('H*','EFBBBF');
		$text = preg_replace("/^$bom/", '', $text);
		return $text;
	}
	
	
	/**
	 * Fourni le cleanname utilisé par la fédégn
	 */
	public static function cleanname($prenom, $nom)
	{
		return strtolower(self::remove_accents($prenom.$nom));
	}
	
	/**
	 * Test si l'utilisateur dispose d'un pass GN
	 */
	public function test(EtatCivil $etatCivil)
	{
		$url = $this->params->get('fedegn.url');
		$password= $this->params->get('fedegn.password');
		$cleanname = $this->cleanname($etatCivil->getPrenom(), $etatCivil->getNom());
		$birthdate = $etatCivil->getDateNaissance()->format('Y-m-d');
		$year = new \Datetime('NOW');
		$year = $year->format('Y'); 
	
		dump($url.'?password='.$password.'&cleanname='.$cleanname.'&birthdate='.$birthdate.'&year='.$year);
		
		$file_content = @file_get_contents($url.'?password='.$password.'&cleanname='.$cleanname.'&birthdate='.$birthdate.'&year='.$year);
		if ( false === $file_content ) {
			return false;
		}
		
		$result = $this->remove_utf8_bom($file_content);
		if ( strcmp($result,"false") === 0 )
		{
			return false;
		}
		else
		{
			return $result;
		}
	}
}
