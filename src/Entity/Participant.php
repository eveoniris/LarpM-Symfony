<?php

namespace App\Entity;

use Doctrine\ORM\Mapping\Entity;

#[Entity(repositoryClass: 'LarpManager\Repository\ParticipantRepository')]
class Participant extends BaseParticipant
{
   public function __construct()
	{
		$this->setSubscriptionDate(new \Datetime('NOW'));
	}
	
	public function __toString()
	{
		return $this->getUser()->getDisplayName();
	}
	
	/**
	 * Verifie si le participant a répondu à cette question
	 */
	public function asAnswser(Question $q)
	{
		foreach( $this->getReponses() as $reponse)
		{
			if ( $reponse->getQuestion() == $q) return true;
		}
		return false;
	}
	
	/**
	 * Vérifie si le joueur est responsable du groupe
	 * @param Groupe $groupe
	 */
	public function isResponsable(Groupe $groupe)
	{
		foreach ( $groupe->getGroupeGns() as $session)
		{
			if ( $this->getGroupeGns()->contains($session)) return true;
		}
		return false;
	}
		
	/**
	 * Fourni la session de jeu auquel participe l'utilisateur
	 */
	public function getSession()
	{
		return $this->getGroupeGn();
	}
	
	/**
	 * Retire un participant d'un groupe
	 */
	public function setGroupeGnNull()
	{
		$this->setGroupeGn(null);
		return $this;
	}
	
	/**
	 * Retire un personnage du participant
	 */
	public function setPersonnageNull()
	{
		$this->setPersonnage(null);
		return $this;
	}
	
	public function getUserIdentity()
	{
		return $this->getUser()->getDisplayName() .' '. $this->getUser()->getEmail();
		
	}
	
	public function getBesoinValidationCi() 
    {
	   return $this->getGn()->getBesoinValidationCi() && $this->getValideCiLe() == null;
	}
	
	/**
	 * Retourne le groupe du groupe gn associé
	 * @return \App\Entity\Groupe
	 */
	public function getGroupe() : ?\App\Entity\Groupe
    {
	    if ($this->getGroupeGn() != null)
	    {
	       return $this->getGroupeGn()->getGroupe();
	    }
	    return null;
    }
  
    /**
	 * Retourne true si le participant a un billet PNJ, false sinon
	 * @return bool
	 */
	public function isPnj() : bool
	{
	    if ($this->getBillet())
	    {
	        return $this->getBillet()->isPnj();
	    }
	    return false;
	}
	
	/**
	 * Retourne le nom complet de l'utilisateur (nom prénom)
	 * @return string
	 */
	public function getUserFullName() : string
	{
	    return $this->getUser()->getFullName();
	    
	}
	
	public function getAgeJoueur() : int
	{
		$gn_date = $this->getGn()->getDateFin();
		$naissance = $this->getUser()->getEtatCivil()->getDateNaissance();
		$interval = date_diff($gn_date, $naissance);

		return intval($interval->format('%y'));
	}

	public function hasPotionsDepartByLevel($niveau=1)
	{
		$return = false;
		foreach ( $this->getPotionsDepart() as $potion)
		{
			if ($potion->getNiveau() == $niveau)
			{
				$return = $potion;
			}
		}
		return $return;
	}

	public function getPotionsDepartByLevel($niveau=1)
	{
		$return = false;
		foreach ( $this->getPotionsDepart() as $potion)
		{
			if ($potion->getNiveau() == $niveau)
			{
				$return = $potion;
			}
		}
		if ($return === false) $return = $this->getPotionsRandomByLevel($niveau);
		return $return;
	}

	public function getPotionsRandomByLevel($niveau=1)
	{
		$potions = array();
		foreach ( $this->getPersonnage()->getPotions() as $potion)
		{
			if ($potion->getNiveau() == $niveau)
			{
				$potions[] = $potion;
			}
		}

		return $potions[rand(0, count($potions)-1)];
	}	

	public function getPotionsEnveloppe()
	{
		$niveauMax = $this->getPersonnage()->getCompetenceNiveau('Alchimie');
		$i = 1;
		$potions = array();
		while ($i <= $niveauMax)
		{
			$potions[] = $this->getPotionsDepartByLevel($i);
			$i++;
		}
		return $potions;
	}
}
