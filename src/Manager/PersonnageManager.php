<?php

namespace App\Manager;

use App\Entity\Personnage;
use App\Entity\Competence;
use App\Entity\CompetenceFamily;
use App\Entity\Religion;
use App\Service\Utilities;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;

final class PersonnageManager
{
	

    /**
	 * Stock le personnage courant de la session
	 * 
	 * @param Personnage $personnage
	 */
	public function setCurrentPersonnage($personnageId)
	{
		$this->app['session']->set('personnageId',$personnageId);
	}
	
	/**
	 * Récupére le personnage courant de la session
	 * @return Personnage $personnage
	 */
	public function getCurrentPersonnage()
	{
		$personnageId = $this->app['session']->get('personnageId');
		if ( $personnageId )
		{
			return $this->app['converter.personnage']->convert($personnageId);
		}
		return null;
	}
	
	/**
	 * Reset le personnage courant
	 */
	public function resetCurrentPersonnage()
	{
		$this->app['session']->set('personnageId', null);
	}
	
	/**
	 * Calcul le cout d'une compétence en fonction de la classe du personnage
	 *
	 * @param Personnage $personnage
	 * @param Competence $competence
	 */
	public function getCompetenceCout(Personnage $personnage, Competence $competence)
	{
		$classe = $personnage->getClasse();
		if ($classe->getCompetenceFamilyFavorites()->contains($competence->getCompetenceFamily()))
		{
			return $competence->getLevel()->getCoutFavori();
		}
		else if ($classe->getCompetenceFamilyNormales()->contains($competence->getCompetenceFamily()))
		{
			return $competence->getLevel()->getCout();
		}
	
		return $competence->getLevel()->getCoutMeconu();
	
	}
	
	/**
	 * Fourni le titre du personnage en fonction de sa renommée
	 *
	 * @param Personnage $personnage
	 */
	public function titre(Personnage $personnage, EntityManagerInterface $entityManager)
	{
	
		$result = null;
		$repo = $entityManager->getRepository('\\'.\App\Entity\Titre::class);
		$titres = $repo->findByRenomme();
		foreach ($titres as $titre )
		{
			if ( $personnage->getRenomme() >= $titre->getRenomme() )
			{
				$result = $titre;
			}
		}
	
		return $result;
	}
		
	/**
	 * Indique si un personnage connait une famille de competence
	 *
	 * @param Personnage $personnage
	 * @param CompetenceFamily $competenceFamily
	 * @return boolean
	 */
	public function knownCompetenceFamily(Personnage $personnage, CompetenceFamily $competenceFamily)
	{
		$competences = $personnage->getCompetences();
	
		foreach ( $competences as $competence)
		{
			if ( $competence->getCompetenceFamily() === $competenceFamily)
			{
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Indique si un personnage connait une religion
	 *
	 * @param Personnage $personnage
	 * @param Religion $religion
	 */
	public function knownReligion(Personnage $personnage, Religion $religion)
	{
		$personnageReligions = $personnage->getPersonnagesReligions();
	
		foreach ( $personnageReligions as $personnageReligion )
		{
			if ( $personnageReligion->getReligion() === $religion)
			{
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Fourni la liste des compétences inconnues d'un personnage
	 *
	 * @param Personnage $personnage
	 * @return Collection $competences
	 */
	public function getUnknownCompetences(Personnage $personnage, EntityManagerInterface $entityManager)
	{
		$unknownCompetences = new ArrayCollection();
	
		$repo = $entityManager->getRepository('\\'.\App\Entity\CompetenceFamily::class);
		$competenceFamilies = $repo->findAll();
	
		foreach ( $competenceFamilies as $competenceFamily)
		{
			if ( ! $this->knownCompetenceFamily($personnage, $competenceFamily))
			{
				$competence = $competenceFamily->getFirstCompetence();
				if ( $competence )
				{
					$unknownCompetences->add($competence);
				}
			}
		}
	
		return $unknownCompetences;
	}
	
	/**
	 * Retourne la liste des toutes les religions inconnues d'un personnage
	 * @param Personnage $personnage
	 */
	public function getAvailableDescriptionReligion(Personnage $personnage, EntityManagerInterface $entityManager)
	{
		$availableDescriptionReligions = new ArrayCollection();
		
		$repo = $entityManager->getRepository('\\'.\App\Entity\Religion::class);
		$religions = $repo->findAll();
		
		foreach ( $religions as $religion)
		{
			if ( ! $personnage->getReligions()->contains($religion)) $availableDescriptionReligions[] = $religion;	
		}
		
		return $availableDescriptionReligions; 
	}
	
	/**
	 * Récupére la liste des toutes les compétences accessibles pour un personnage
	 *
	 * @param Personnage $personnage
	 * @return Collection $competenceNiveaux
	 */
	public function getAvailableCompetences(Personnage $personnage, EntityManagerInterface $entityManager)
	{
		$availableCompetences = new ArrayCollection();
	
		// les compétences de niveau supérieur sont disponibles
		$competences = $personnage->getCompetences();
		foreach ( $competences as $competence )
		{
			$nextCompetence = $competence->getNext();
			if ( $nextCompetence &&  ! $personnage->getCompetences()->contains($nextCompetence) )
			{
				$availableCompetences->add($nextCompetence);
			}
		}
	
		// les compétences inconnues du personnage sont disponibles au niveau 1
		$competences = $this->getUnknownCompetences($personnage, $entityManager);
	
		foreach ($competences as $competence )
		{
			$availableCompetences->add($competence);
		}
	
		// trie des competences disponibles
		$iterator = $availableCompetences->getIterator();
		$iterator->uasort(function ($a, $b) {
			return ($a->getLabel() < $b->getLabel()) ? -1 : 1;
		});
	
		return  new ArrayCollection(iterator_to_array($iterator));
	}
	
	/**
	 * Trouve toutes les langues non connues d'un personnages en fonction du niveau de diffusion voulu
	 * @param Personnage $langue
	 * @param unknown $diffusion
	 */
	public function getAvailableLangues(Personnage $personnage, $diffusion, EntityManagerInterface $entityManager)
	{
		$availableLangues = new ArrayCollection();
	
		$repo = $entityManager->getRepository('\\'.\App\Entity\Langue::class);
		$langues = $repo->findBy(array(),array('label' => 'ASC'));
	
		foreach ( $langues as $langue)
		{
			if ( $langue->getSecret() == 0)
			{
				if ( $diffusion == 0 )
				{
					if ( $langue->getDiffusion() == $diffusion
					&& ! $personnage->isKnownLanguage($langue) )
					{
						$availableLangues[] = $langue;
					}
				}
				else if ( $langue->getDiffusion() >= $diffusion
						&& ! $personnage->isKnownLanguage($langue) )
				{
					$availableLangues[] = $langue;
				}
			}
		}
		return $availableLangues;
	}

	/**
	 * Trouve tous les sorts non connus d'un personnage en fonction du niveau du sort
	 * @param Personnage $personnage
	 * @param unknown $diffusion
	 */
	public function getAvailableSorts(Personnage $personnage, $niveau, EntityManagerInterface $entityManager)
	{
		$availableSorts = new ArrayCollection();
	
		$repo = $entityManager->getRepository('\\'.\App\Entity\Sort::class);
		$sorts = $repo->findByNiveau($niveau);
			
		foreach ( $sorts as $sort)
		{
			if ( ! $personnage->isKnownSort($sort) )
			{
				$availableSorts[] = $sort;
			}
		}
		return $availableSorts;
	}
	
	/**
	 * Trouve tous les domaines de magie non connus d'un personnage 
	 * @param Personnage $personnage
	 */
	public function getAvailableDomaines(Personnage $personnage, EntityManagerInterface $entityManager)
	{
		$availableDomaines = new ArrayCollection();
	
		$repo = $entityManager->getRepository('\\'.\App\Entity\Domaine::class);
		$domaines = $repo->findAll();
	
		foreach ( $domaines as $domaine)
		{
			if ( ! $personnage->isKnownDomaine($domaine) )
			{
				$availableDomaines[] = $domaine;
			}
		}
		return $availableDomaines;
	}
	
	/**
	 * Récupére la liste de toutes les religions non connues du personnage
	 * @param Personnage $personnage
	 */
	public function getAvailableReligions(Personnage $personnage, EntityManagerInterface $entityManager)
	{
		$availableReligions = new ArrayCollection();
	
		$repo = $entityManager->getRepository('\\'.\App\Entity\Religion::class);
		$religions = $repo->findAllPublicOrderedByLabel();
	
		foreach ( $religions as $religion)
		{
			if ( ! $this->knownReligion($personnage, $religion))
			{
				$availableReligions->add($religion);
			}
		}
	
		return $availableReligions;
	}

	/**
	 * Récupére la liste de toutes les religions non connue du personnage, vue admin
	 * @param Personnage $personnage
	 */
	public function getAdminAvailableReligions(Personnage $personnage, EntityManagerInterface $entityManager)
	{
		$availableReligions = new ArrayCollection();
		
		$repo = $entityManager->getRepository('\\'.\App\Entity\Religion::class);
		$religions = $repo->findAllOrderedByLabel();
		
		foreach ( $religions as $religion)
		{
			if ( ! $this->knownReligion($personnage, $religion))
			{
				$availableReligions->add($religion);
			}
		}
		
		return $availableReligions;
	}
	
	/**
	 * Fourni la dernière compétence acquise par un presonnage
	 *
	 * @param Personnage $personnage
	 */
	public function getLastCompetence(Personnage $personnage)
	{
		$competence = null;
		$operationDate = null;
	
		foreach ( $personnage->getExperienceUsages() as $experienceUsage)
		{
			if ( $personnage->getCompetences()->contains($experienceUsage->getCompetence()) )
			{
				if ( ! $operationDate )
				{
					$operationDate = $experienceUsage->getOperationDate();
					$competence = $experienceUsage->getCompetence();
				}
				else if ( $operationDate <  $experienceUsage->getOperationDate() )
				{
					$operationDate = $experienceUsage->getOperationDate();
					$competence = $experienceUsage->getCompetence();
				}
			}
		}
	
		return $competence;	
	}

	/**
	 * Trouve toutes les technologies non connues d'un personnage
	 * @param Personnage $personnage
	 * @param unknown $diffusion
	 */
	public function getAvailableTechnologies(Personnage $personnage, EntityManagerInterface $entityManager)
	{
		$availableTechnologies = new ArrayCollection();
	
		$repo = $entityManager->getRepository('\\'.\App\Entity\Technologie::class);
		$technologies = $repo->findPublicOrderedByLabel();
			
		foreach ( $technologies as $technologie)
		{
			if ( ! $personnage->isKnownTechnologie($technologie) )
			{
				$availableTechnologies[] = $technologie;
			}
		}
		return $availableTechnologies;
	}    

	/*********************************************** */

	/**
     * Tri du tableau personnages suivant le sortFieldName spécifié, asc ou desc.
     * Le tableau passé en paramètre est directement modifié.
     * Valeurs de nom de tri supportées :
     * - pugilat
     * - heroisme
     * - user
     * - hasAnomalie
     * - status
     * @param array $personnages
     * @param string $sortFieldName
     * @param bool $isAsc
     */
    public static function sort(array &$personnages, string $sortFieldName, bool $isAsc)
    {
        switch ($sortFieldName)
        {
            case 'id':
                $sortByFunctionName = 'sortById';
                break;
            case 'status':
                $sortByFunctionName = 'sortByStatus';
                break;
            case 'nom':
                $sortByFunctionName = 'sortByNom';
                break;
            case 'classe':
                $sortByFunctionName = 'sortByClasse';
                break;
            case 'groupe':
                $sortByFunctionName = 'sortByGroupe';
                break;
            case 'renomme':
                $sortByFunctionName = 'sortByRenomme';
                break;
            case 'pugilat':
                $sortByFunctionName = 'sortByPugilat';
                break;
            case 'heroisme':
                $sortByFunctionName = 'sortByHeroisme';
                break;
            case 'user':
                $sortByFunctionName = 'sortByUser';
                break;
            case 'xp':
                $sortByFunctionName = 'sortByXp';
                break;
            case 'hasAnomalie':
                $sortByFunctionName = 'sortByHasAnomalie';
                break;
            default:
                throw new \Exception('Le champ de tri '.$sortFieldName.' n\'a pas été implémenté');
        }
        if (!$isAsc)
        {
            $sortByFunctionName = $sortByFunctionName.'Desc';
        }
        

        //PersonnageManager::stable_uasort($personnages, array('\App\Manager\PersonnageManager', $sortByFunctionName)); 
        
        //$sortByFunctionName = 'PersonnageManager::'.$sortByFunctionName;
        
        $index = 0;
        foreach ($personnages as &$item) {
            $item = array($index++, $item);
        }
        $result = uasort($personnages, function($a, $b) use($sortByFunctionName) {
            $result = call_user_func(__NAMESPACE__ . '\PersonnageManager::' . $sortByFunctionName, $a[1], $b[1]);
            return $result == 0 ? $a[0] - $b[0] : $result;
        });
        foreach ($personnages as &$item) {
            $item = $item[1];
        }
        return $result;
    }

    
    
    /**
     * Tri sur Id
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortById(Personnage $a, Personnage $b)
    {
        return Utilities::sortBy($a->getId(), $b->getId());
    }
    
    /**
     * Tri sur Id Desc
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByIdDesc(Personnage $a, Personnage $b)
    {
        return self::sortById($b, $a);
    }
    
    /**
     * Tri sur Classe
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByClasse(Personnage $a, Personnage $b)
    {
        return Utilities::sortBy($a->getClasseName(), $b->getClasseName());
    }
    
    /**
     * Tri sur Classe Desc
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByClasseDesc(Personnage $a, Personnage $b)
    {
        return self::sortByClasse($b, $a);
    }
    
    /**
     * Tri sur Groupe
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByGroupe(Personnage $a, Personnage $b)
    {
        return Utilities::sortBy($a->getLastParticipantGnGroupeNom(), $b->getLastParticipantGnGroupeNom());
    }
    
    /**
     * Tri sur Groupe Desc
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByGroupeDesc(Personnage $a, Personnage $b)
    {
        return self::sortByGroupe($b, $a);
    }
    
    /**
     * Tri sur Renommée
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByRenomme(Personnage $a, Personnage $b)
    {
        return Utilities::sortBy($a->getRenomme(), $b->getRenomme());
    }
    
    /**
     * Tri sur Groupe Desc
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByRenommeDesc(Personnage $a, Personnage $b)
    {
        return self::sortByRenomme($b, $a);
    }
    
    /**
     * Tri sur points d'expérience
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByXp(Personnage $a, Personnage $b)
    {
        return Utilities::sortBy($a->getXp(), $b->getXp());
    }
    
    /**
     * Tri sur points d'expérience Desc
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByXpDesc(Personnage $a, Personnage $b)
    {
        return self::sortByXp($b, $a);
    }
    
    /**
     * Tri sur Pugilat
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByPugilat(Personnage $a, Personnage $b)
    {
        return Utilities::sortBy($a->getPugilat(), $b->getPugilat());
    }
    
    /**
     * Tri sur Pugilat Desc
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByPugilatDesc(Personnage $a, Personnage $b)
    {
        return self::sortByPugilat($b, $a);
    }
    
    /**
     * Tri sur Heroisme
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByHeroisme(Personnage $a, Personnage $b)
    {
        return Utilities::sortBy($a->getHeroisme(), $b->getHeroisme());
    }
    
    /**
     * Tri sur Heroisme Desc
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByHeroismeDesc(Personnage $a, Personnage $b)
    {
        return self::sortByHeroisme($b, $a);
    }
    
    /**
     * Tri sur User Full Name
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByUser(Personnage $a, Personnage $b)
    {
        return Utilities::sortBy($a->getUserFullName(), $b->getUserFullName());
    }
    
    /**
     * Tri sur User Full Name Desc
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByUserDesc(Personnage $a, Personnage $b)
    {
        return self::sortByUser($b, $a);
    }
    
    /**
     * Tri sur HasAnomalie
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByHasAnomalie(Personnage $a, Personnage $b)
    {
        return Utilities::sortBy($a->hasAnomalie(), $b->hasAnomalie());
    }
    
    /**
     * Tri sur HasAnomalieDesc
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByHasAnomalieDesc(Personnage $a, Personnage $b)
    {
        return self::sortByHasAnomalie($b, $a);
    }
    
    /**
     * Tri sur Status Code
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByStatusCode(Personnage $a, Personnage $b)
    {
        return Utilities::sortBy($a->getStatusCode(), $b->getStatusCode());
    }
    
    /**
     * Tri sur Status Code Desc
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByStatusCodeDesc(Personnage $a, Personnage $b)
    {
        return self::sortByStatus($b, $a);
    }
    
    /**
     * Tri sur Status On Active GN
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByStatusOnActiveGn(Personnage $a, Personnage $b)
    {
        return Utilities::sortBy($a->getStatusOnActiveGnCode(), $b->getStatusOnActiveGnCode());
    }
    
    /**
     * Tri sur Status On Active GN Desc
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByStatusOnActiveGnDesc(Personnage $a, Personnage $b)
    {
        return self::sortByStatusOnActiveGn($b, $a);
    }
    
    /**
     * Tri sur Nom
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByNom(Personnage $a, Personnage $b)
    {
        return Utilities::sortBy($a->getNom(), $b->getNom());
    }
    
    /**
     * Tri sur Nom Desc
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByNomDesc(Personnage $a, Personnage $b)
    {
        return self::sortByNom($b, $a);
    }
    
    /**
     * Tri sur Status GN, du + récent (+ grand) au - récent (+ petit) puis par nom ASC
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByStatusGn(Personnage $a, Personnage $b)
    {
        $aStatus = $a->getStatusGnCode();
        $bStatus = $b->getStatusGnCode();
        if ($aStatus == $bStatus) {
            return self::sortByNom($a, $b);
        }
        // on prend le statut à l'envers, ici 0 = mort donc on veut plutôt du + grand au + petit
        return ($aStatus > $bStatus) ? -1 : 1;
    }
    
    /**
     * Tri sur Status GN DESC, du - récent (+ petit) au + récent (+ grand) puis par nom DESC
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByStatusGnDesc(Personnage $a, Personnage $b)
    {
        return self::sortByStatusGn($b, $a);
    }
    
    /**
     * Tri sur Last Participant GN Number, du + récent (+ grand) au - récent (+ petit) puis par nom ASC
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByLastParticipantGnNumber(Personnage $a, Personnage $b)
    {
        $aStatus = $a->getLastParticipantGnNumber();
        $bStatus = $b->getLastParticipantGnNumber();
        if ($aStatus == $bStatus) {
            return self::sortByNom($a, $b);
        }
        // on prend le statut à l'envers, ici 0 = mort donc on veut plutôt du + grand au + petit
        return ($aStatus > $bStatus) ? -1 : 1;
    }
    
    /**
     * Tri sur Last Participant GN Number DESC, du - récent (+ petit) au + récent (+ grand) puis par nom DESC
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByLastParticipantGnNumberDesc(Personnage $a, Personnage $b)
    {
        return self::sortByLastParticipantGnNumber($b, $a);
    }
    
    /**
     * Tri sur Status :
     * - d'abord les PJs vivants sur le GN actif,
     * - puis les PNJ,
     * - puis les PJ anciens,
     * - puis les morts,
     * et pour chaque groupe, du + récent gn (+ grand) au - récent (+ petit) puis par nom ASC
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByStatus(Personnage $a, Personnage $b)
    {
        $aStatus = $a->getStatusOnActiveGnCode();
        $bStatus = $b->getStatusOnActiveGnCode();
        
        // si les 2 sont pnj ou les 2 sont morts, on se base sur le gn
        if ($a->isPnj() && $b->isPnj() || !$a->getVivant() && !$b->getVivant())
        {
            return self::sortByLastParticipantGnNumber($a, $b);
        }
        if ($aStatus == $bStatus) {
            return self::sortByStatusGn($a, $b);
        }
        // on prend le statut à l'envers, ici 0 = mort donc on veut plutôt du + grand au + petit
        return ($aStatus > $bStatus) ? -1 : 1;
    }
    
    /**
     * Tri sur Status DESC:
     * - d'abord les morts,
     * - puis les PJ anciens,
     * - puis les PNJ,
     * - puis les PJs vivants sur le GN actif
     * et pour chaque groupe, du - récent gn (+ petit) au + récent (+ grand) puis par nom DESC
     * @param Personnage $a
     * @param Personnage $b
     * @return number
     */
    public static function sortByStatusDesc(Personnage $a, Personnage $b)
    {
        return self::sortByStatus($b,$a);
    }
}
