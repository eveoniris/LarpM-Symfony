<?php

namespace App\Service;

use App\Entity\Classe;
use App\Entity\Competence;
use App\Entity\CompetenceFamily;
use App\Entity\ExperienceGain;
use App\Entity\ExperienceUsage;
use App\Entity\Level;
use App\Entity\LogAction;
use App\Entity\Merveille;
use App\Entity\Personnage;
use App\Entity\PersonnageTrigger;
use App\Entity\RenommeHistory;
use App\Enum\CompetenceFamilyType;
use App\Service\Competence\AlchimieService;
use App\Service\Competence\ArtisantService;
use App\Service\Competence\LitteratureService;
use App\Service\Competence\MagieService;
use App\Service\Competence\NoblesseService;
use App\Service\Competence\PretriseService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CompetenceService
{
    public const ERR_CODE_XP = 1;
    public const ERR_CODE_LEARN = 2;

    public const COUT_GRATUIT = 0;
    public const COUT_DEFAUT = -1;

    protected ?Competence $competence;
    protected ?CompetenceFamily $competenceFamily;
    protected ?Level $competenceLevel;
    protected ?Personnage $personnage;
    protected ?Classe $classe;
    protected array $errors;
    protected bool $hasBonus = false;

    public function __construct(
        protected readonly EntityManagerInterface $entityManager,
        protected readonly UrlGeneratorInterface $urlGenerator,
        protected readonly Security $security,
        protected readonly ConditionsService $conditionService,
    ) {
        $this->init();
    }

    public function init(?Personnage $personnage = null, ?Competence $competence = null): self
    {
        $this->reset()
            ->setPersonnage($personnage)
            ->setCompetence($competence);

        return $this;
    }

    public function reset(): self
    {
        return $this->resetErrors()
            ->setCompetence(null)
            ->setPersonnage(null)
            ->setClasse(null)
            ->setCompetenceLevel(null)
            ->setCompetenceFamily(null);
    }

    final public function resetErrors(): self
    {
        $this->errors = [];

        return $this;
    }

    public function addCompetence(int $cout = self::COUT_DEFAUT): self
    {
        if (!$this->canLearn($cout)) {
            return $this;
        }

        // Pré-Ajout en base
        $this->getPersonnage()->addCompetence($this->getCompetence());
        $this->getCompetence()->addPersonnage($this->getPersonnage());

        // Consommation d'expérience et historisation
        $this->consumeXP($cout);

        // Attribution des bonus spécifique à la compétence
        $this->giveBonus();

        $log = new LogAction();
        $log->setDate(new \DateTime());
        $log->setType('AddCompetence');
        $log->setUser($this->security->getUser());
        $log->setData([
            'competence_id' => $this->getCompetence()->getId(),
            'personnage_id' => $this->getPersonnage()->getId(),
            'cost' => $cout,
        ]);

        // Enregistrement en base du lot
        $this->entityManager->persist($log);
        $this->entityManager->persist($this->getCompetence());
        $this->entityManager->persist($this->getPersonnage());
        $this->entityManager->flush();

        return $this;
    }

    /**
     * Indique si une class de compétence héritée peut être apprise
     * Si le cout n'est pas précisé, on prend le cout par défaut de la compétence pour la classe du personnage
     * Si le cout vaut 0 on considère alors que la compétence est gratuite ou offerte.
     */
    final public function canLearn(int $cout = self::COUT_DEFAUT): bool
    {
        if ($cout < 0) {
            $cout = $this->getCompetenceCout();
        }

        // Si un personnage à un XP négatif. Il ne peut apprendre que des gratuites
        if ($cout > 0 && $this->getPersonnage()->getXp() - $cout < 0) {
            $this->addError(
                "Vous n'avez pas suffisamment de points d'expérience pour acquérir cette compétence.",
                self::ERR_CODE_XP,
            );
        }

        $this->validateApprendre();

        return !$this->hasErrors();
    }

    /**
     * Calcul le cout d'une compétence en fonction de la classe du personnage.
     */
    public function getCompetenceCout(): int
    {
        $bonusCout = $this->getBonusCout();

        if (Level::NIVEAU_1 === $this->getCompetenceLevel()->getIndex()
            && $this->getClasse()->getCompetenceFamilyCreations()->contains($this->getCompetenceFamily())
        ) {
            return 0;
        }

        if ($this->getClasse()->getCompetenceFamilyFavorites()->contains($this->getCompetenceFamily())) {
            return max($this->getCompetenceLevel()->getCoutFavori() - $bonusCout, 0);
        }

        if ($this->getClasse()->getCompetenceFamilyNormales()->contains($this->getCompetenceFamily())) {
            return max($this->getCompetenceLevel()->getCout() - $bonusCout, 0);
        }

        return max($this->getCompetenceLevel()->getCoutMeconu() - $bonusCout, 0);
    }

    public function getBonusCout(): int
    {
        return $this->getOrigineBonusCout()
            + $this->getMerveilleBonusCout()
            + $this->getApprentissageBonusCout();
    }

    public function getOrigineBonusCout(): int
    {
        $count = 0;

        // On ne prend que les bonus encore actif
        foreach ($this->getPersonnage()->getOrigine()?->getValideOrigineBonus() as $origineBonus) {
            $bonus = $origineBonus->getBonus();
            // Dans ce service, nous ne traitons que les bonus de type XP
            // Enfin, nous vérifions que le bonus est pour une compétence donnée ou non.
            if (
                $bonus->isXp()
                && (
                    // null === $bonus->getCompetence() || TODO : at this time we do not allow to give xp to ALL competences
                    $this->getCompetence()->getId() === $bonus->getCompetence()?->getId()
                )
            ) {
                $count += $bonus->getValeur();
            }
        }

        return $count;
    }

    public function getPersonnage(): Personnage
    {
        if (!isset($this->personnage)) {
            throw new \RuntimeException('Personnage is not set');
        }

        return $this->personnage;
    }

    public function setPersonnage(?Personnage $personnage): self
    {
        $this->personnage = $personnage;

        if ($personnage) {
            $this->classe = $personnage->getClasse();
        }

        return $this;
    }

    public function getCompetence(): Competence
    {
        if (!isset($this->competence)) {
            throw new \RuntimeException('Competence is not set');
        }

        return $this->competence;
    }

    public function setCompetence(?Competence $competence): self
    {
        $this->competence = $competence;
        if ($competence) {
            $this->setCompetenceFamily($competence->getCompetenceFamily());
            $this->setCompetenceLevel($competence->getLevel());
        }

        return $this;
    }

    public function getMerveilleBonusCout(): int // PersonnageApprentissage
    {
        $count = 0;

        foreach ($this->getPersonnage()?->getLastParticipant()?->getGroupe()?->getTerritoires() ?? [] as $territoire) {
            /** @var Merveille $merveille */
            foreach ($territoire->getMerveilles() as $merveille) {
                if (!$merveille->isActive()) {
                    continue;
                }

                $bonus = $merveille->getBonus();

                if (!$bonus || !$bonus->isValid()) {
                    continue;
                }

                if (!$this->conditionService->isValidConditions(
                    $this->getPersonnage(),
                    $bonus->getJsonData()['condition'] ?? [],
                    $bonus,
                )
                ) {
                    //    continue;
                }

                if (!$this->conditionService->isValidConditions(
                    $this->getCompetence()->getCompetenceFamily(),
                    $bonus->getJsonData()['condition'] ?? [],
                    $bonus,
                )
                ) {
                    continue;
                }

                // Dans ce service, nous ne traitons que les bonus de type XP
                // Enfin, nous vérifions que le bonus est pour une compétence donnée ou non.
                if (
                    $bonus->isXp()
                    && (
                        null === $bonus->getCompetence()
                        || $this->getCompetence()->getId() === $bonus->getCompetence()->getId()
                    )
                ) {
                    $count += $bonus->getValeur();
                }
            }
        }

        return $count;
    }

    public function getCompetenceFamily(): CompetenceFamily
    {
        if (!isset($this->competenceFamily)) {
            throw new \RuntimeException('Competence family is not set');
        }

        return $this->competenceFamily;
    }

    public function setCompetenceFamily(?CompetenceFamily $competenceFamily): self
    {
        $this->competenceFamily = $competenceFamily;

        return $this;
    }

    public function getApprentissageBonusCout(): int
    {
        $apprentissage = $this->getPersonnage()->getApprentissage($this->getCompetence());
        if ($apprentissage) {
            return 1;
        }

        return 0;
    }

    public function getCompetenceLevel(): Level
    {
        if (!isset($this->competenceLevel)) {
            throw new \RuntimeException('Competence level is not set');
        }

        return $this->competenceLevel;
    }

    public function setCompetenceLevel(?Level $competenceLevel): self
    {
        $this->competenceLevel = $competenceLevel;

        return $this;
    }

    public function getClasse(): Classe
    {
        if (!isset($this->classe)) {
            throw new \RuntimeException('Classe is not set');
        }

        return $this->classe;
    }

    public function setClasse(?Classe $classe): self
    {
        $this->classe = $classe;

        return $this;
    }

    final public function addError($error, ?int $code = null): void
    {
        $code ? $this->errors[$code] = $error : $this->errors[] = $error;
    }

    /**
     * Étendre cette méthode et appeler $this->addError() au besoin.
     */
    protected function validateApprendre(): void
    {
    }

    final public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Consomme les points d'xp d'un personnage et historise l'usage
     * Si le cout vaut 0 alors la compétence a été offerte.
     */
    public function consumeXP(int $cout): self
    {
        $this->getPersonnage()->setXp($this->getPersonnage()->getXp() - $cout);

        $historique = new ExperienceUsage();
        $historique->setOperationDate(new \DateTime('NOW'));
        $historique->setXpUse($cout);
        $historique->setCompetence($this->getCompetence());
        $historique->setPersonnage($this->getPersonnage());
        $this->entityManager->persist($historique);

        $apprentissage = $this->getPersonnage()->getApprentissage($this->getCompetence());
        if ($apprentissage) {
            $apprentissage->setDateUsage(new \DateTime('NOW'));
            $this->entityManager->persist($apprentissage);
        }

        return $this;
    }

    /**
     * Donne les bonus de la compétence au personnage.
     */
    final public function giveBonus(): void
    {
        if (!$this->canGetBonus()) {
            return;
        }

        $this->give();
    }

    /**
     * Détermine si le personnage peut recevoir des bonus.
     */
    public function canGetBonus(): bool
    {
        return $this->hasBonus();
    }

    /**
     * Indique si une class de compétence héritée donne droit à des bonus.
     */
    public function hasBonus(): bool
    {
        return $this->hasBonus;
    }

    protected function give(): void
    {
        throw new \RuntimeException('Method "give" is not implemented for competence:'.static::class);
    }

    public function addRenomme(int $value, string $explication): self
    {
        $this->getPersonnage()->addRenomme($value);
        $renommeHistory = new RenommeHistory();
        $renommeHistory->setRenomme($value);
        $renommeHistory->setExplication($explication);
        $renommeHistory->setPersonnage($this->getPersonnage());
        $this->entityManager->persist($renommeHistory);

        return $this;
    }

    public function getCompetenceService(Competence $competence): self
    {
        $service = match ($competence->getCompetenceFamily()?->getCompetenceFamilyType()?->value) {
            CompetenceFamilyType::PRIESTHOOD->value => $this->getService(PretriseService::class),
            CompetenceFamilyType::NOBILITY->value => $this->getService(NoblesseService::class),
            CompetenceFamilyType::ALCHEMY->value => $this->getService(AlchimieService::class),
            CompetenceFamilyType::MAGIC->value => $this->getService(MagieService::class),
            CompetenceFamilyType::CRAFTSMANSHIP->value => $this->getService(ArtisantService::class),
            CompetenceFamilyType::LITERATURE->value => $this->getService(LitteratureService::class),
            default => $this,
        };

        return $service->setCompetence($competence);
    }

    public function getService(string $class): self
    {
        return new $class(
            $this->entityManager,
            $this->urlGenerator,
            $this->security,
            $this->conditionService,
        );
    }

    final public function getErrors(): array
    {
        return $this->errors;
    }

    final public function getErrorsAsString(): string
    {
        return implode(', '.PHP_EOL, $this->errors);
    }

    /**
     * @return Collection<int, Competence>
     */
    public function getOrigineBonusCompetences(?Personnage $personnage = null): ArrayCollection
    {
        $competences = new ArrayCollection();
        foreach (($personnage ?? $this->getPersonnage())->getOrigine()?->getValideOrigineBonus() as $origineBonus) {
            $bonus = $origineBonus->getBonus();
            if ($bonus->isCompetence() && (null !== $bonus->getCompetence())) {
                $competences->add($bonus->getCompetence());
            }
        }

        return $competences;
    }

    public function removeCompetence(int $cout = self::COUT_DEFAUT): self
    {
        // Pré-update en base
        $this->getPersonnage()->removeCompetence($this->getCompetence());
        $this->getCompetence()->removePersonnage($this->getPersonnage());

        // Consommation d'expérience et historisation
        $this->giveXP($cout, 'Suppression de la compétence '.$this->getCompetence()->getLabel());

        // Retrait des bonus
        $this->removeBonus();

        // Enregistrement en base du lot
        $this->entityManager->persist($this->getCompetence());
        $this->entityManager->persist($this->getPersonnage());
        $this->entityManager->flush();

        return $this;
    }

    public function giveXP(int $cout, string $explanation): self
    {
        $this->getPersonnage()->setXp($this->getPersonnage()->getXp() + $cout);

        $historique = new ExperienceGain();
        $historique->setOperationDate(new \DateTime('NOW'));
        $historique->setXpGain($cout);
        $historique->setExplanation($explanation);
        $historique->setPersonnage($this->getPersonnage());
        $this->entityManager->persist($historique);

        return $this;
    }

    final public function removeBonus(): void
    {
        if (!$this->canGetBonus()) {
            return;
        }

        $this->remove();
    }

    protected function remove(): void
    {
        throw new \RuntimeException('Method "remove" is not implemented for competence:'.static::class);
    }

    public function removeRenomme(int $value, string $explication): self
    {
        $this->getPersonnage()->removeRenomme($value);
        $renommeHistory = new RenommeHistory();
        $renommeHistory->setDate(new \DateTime('NOW'));
        $renommeHistory->setRenomme($value);
        $renommeHistory->setExplication($explication);
        $renommeHistory->setPersonnage($this->getPersonnage());
        $this->entityManager->persist($renommeHistory);

        return $this;
    }

    protected function applyRules(array $rules): void
    {
        $rule = $rules[$this->getCompetence()->getLevel()?->getIndex()] ?? [];
        if (empty($rule)) {
            return;
        }
        foreach ($rule as $tagName => $nb) {
            while ($nb-- >= 0) {
                $trigger = new PersonnageTrigger();
                $trigger->setPersonnage($this->getPersonnage());
                $trigger->setTag($tagName);
                $trigger->setDone(false);
                $this->entityManager->persist($trigger);
            }
        }
    }

    protected function removeRules(array $rules): void
    {
        $rule = $rules[$this->getCompetence()->getLevel()?->getIndex()] ?? [];
        if (empty($rule)) {
            return;
        }

        foreach ($rule as $tagName => $nb) {
            while ($nb-- > 0) {
                // TODO FIND & REMOVE TRIGGER
                /*
                $trigger = new PersonnageTrigger();
                $trigger->setPersonnage($this->getPersonnage());
                $trigger->setTag($tagName);
                $trigger->setDone(false);
                $this->entityManager->persist($trigger);
                */
            }
        }
    }
}
