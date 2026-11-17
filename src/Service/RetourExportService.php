<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Depouillement;
use App\Entity\FicheRetourGroupe;
use App\Entity\Gn;
use App\Enum\DepouillementType;
use App\Repository\DepouillementRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Exports CSV pour le responsable du wargame : fiches de retour de groupe (mêmes colonnes que l'import, donc ré-importables),
 * retours de groupe (titres, ordres par phase et leur validation) et retours des joueurs.
 *
 * Fichiers en UTF-8 avec BOM et séparateur « , », comme le modèle d'import. Les textes libres qui commencent par un caractère de
 * formule d'un tableur (= + - @) sont préfixés d'une apostrophe pour ne jamais être exécutés à l'ouverture.
 */
// @mago-expect lint:cyclomatic-complexity
// @mago-expect lint:kan-defect
readonly class RetourExportService
{
    /** @var list<string> */
    public const array ENTETES_FICHES = [
        'Submitted at',
        'Groupe concerné',
        "Nb de Pièces d'argent",
        "Nb de Pièces d'Or",
        "Nb total d'ingrédients",
        'Nb total de potions',
        'Armement',
        'Chevaux',
        'Fruits légumes',
        'M simples',
        'Sel',
        'Bétail',
        'Coton',
        'Gemmes',
        'Moutons',
        'Soie',
        'Bois',
        'Esclaves',
        'Ivoire',
        'Pierre',
        'Teinture',
        'Céréales',
        'Fourrures',
        'M précieux',
        'Poisson',
        'Vin',
        "Commentaire concernant l'enveloppe",
        'groupe_numero',
    ];

    // @mago-expect lint:excessive-parameter-list
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DepouillementRepository $depouillementRepository,
        private DepouillementRetourGroupeService $retourGroupe,
        private DepouillementOrdresService $ordres,
        private DepouillementSuiviGroupeService $suivi,
        private DepouillementContenuService $contenu,
        private DepouillementDeclarationService $declaration,
    ) {
    }

    /**
     * @return list<list<string|int>>
     */
    public function fichesGroupe(Gn $gn): array
    {
        $lignes = [self::ENTETES_FICHES];
        /** @var list<FicheRetourGroupe> $fiches */
        $fiches = $this->entityManager
            ->createQuery('SELECT f, gg FROM ' . FicheRetourGroupe::class . ' f JOIN f.groupeGn gg WHERE gg.gn = :gn ORDER BY gg.id')
            ->setParameter('gn', $gn)
            ->getResult();
        foreach ($fiches as $fiche) {
            $groupeGn = $fiche->getGroupeGn();

            $lignes[] = [
                $fiche->getSubmittedAt()?->format('Y-m-d H:i:s') ?? '',
                $groupeGn->getGroupe()->getNumero() . ' ' . $groupeGn->getGroupe()->getNom(),
                $fiche->getPiecesArgent(),
                $fiche->getPiecesOr(),
                $fiche->getNbIngredients(),
                $fiche->getNbPotions(),
                $fiche->getArmement(),
                $fiche->getChevaux(),
                $fiche->getFruitsLegumes(),
                $fiche->getMatieresSimples(),
                $fiche->getSel(),
                $fiche->getBetail(),
                $fiche->getCoton(),
                $fiche->getGemmes(),
                $fiche->getMoutons(),
                $fiche->getSoie(),
                $fiche->getBois(),
                $fiche->getEsclaves(),
                $fiche->getIvoire(),
                $fiche->getPierre(),
                $fiche->getTeinture(),
                $fiche->getCereales(),
                $fiche->getFourrures(),
                $fiche->getMatieresPrecieux(),
                $fiche->getPoisson(),
                $fiche->getVin(),
                self::texte($fiche->getCommentaire()),
                (int) $groupeGn->getGroupe()->getNumero(),
            ];
        }

        return $lignes;
    }

    /**
     * @return list<list<string|int>>
     */
    public function retoursGroupe(Gn $gn): array
    {
        $lignes = [[
            'Groupe n°',
            'Groupe',
            'Statut',
            ...array_values(RetourGroupe::TITRES),
            'Ordres phase 1',
            'Phase 1 validée',
            'Ordres phase 2',
            'Phase 2 validée',
            'Ordres phase 3',
            'Phase 3 validée',
            'Précisions',
            'Retours reçus',
            'Retours attendus',
            'Clôturés',
        ]];

        foreach ($this->dossiers($gn, DepouillementType::GROUPE) as $dossier) {
            $groupe = $dossier->getGroupeGn()?->getGroupe();
            $retour = $this->retourGroupe->lire($dossier);
            $phases = $this->ordres->phases($dossier);
            $suivi = $this->suivi->suivi($dossier);
            $ligne = [$groupe?->getNumero() ?? '', $groupe?->getNom() ?? '', $dossier->getStatut()->label()];
            foreach (array_keys(RetourGroupe::TITRES) as $cle) {
                $ligne[] = $retour->titres[$cle] ?? '';
            }

            foreach ([1 => $retour->ordres1, 2 => $retour->ordres2, 3 => $retour->ordres3] as $numero => $texte) {
                $ligne[] = self::texte($texte);
                $ligne[] = isset($phases[$numero]) ? ($phases[$numero]['valide'] ? 'oui' : 'non') : '';
            }

            $lignes[] = [...$ligne, self::texte($retour->precisions), $suivi['recus'], $suivi['attendus'], $suivi['clotures']];
        }

        return $lignes;
    }

    /**
     * @return list<list<string|int>>
     */
    public function retoursJoueurs(Gn $gn, bool $avecEtatCivil = false): array
    {
        $noms = [];
        $lignes = [[
            'Personnage n°',
            'Personnage',
            'Groupe',
            'Statut',
            'Sorti du jeu',
            'Détails de la sortie',
            'Mort',
            'Circonstances de la mort',
            'Sorts',
            'Prières',
            'Potions',
            'Langues',
            'Connaissances',
            'Technologies',
            'Compétence apprise',
            'Enseignant n°',
            'Enseignements donnés',
            'Constructions',
            'Renommée',
            'Renommée (points)',
            'Précisions',
            'Autres informations',
            'Introuvables',
        ]];

        foreach ($this->dossiers($gn, DepouillementType::JOUEUR) as $dossier) {
            $participant = $dossier->getParticipant();
            $etat = $participant?->getUser()?->getEtatCivil();
            $noms[] = $etat ? self::texte(trim((string) $etat->getPrenom() . ' ' . (string) $etat->getNom())) : '';
            $contenu = $this->contenu->lireContenuJoueur($dossier);
            $declaration = $this->declaration->lire($dossier);
            $lignes[] = [
                $dossier->getNumero(),
                self::texte($participant?->getPersonnage()?->getPublicName()),
                self::texte($participant?->getGroupeGn()?->getGroupe()->getNom()),
                $dossier->getStatut()->label(),
                $declaration->sortiDuJeu ? 'oui' : 'non',
                self::texte($declaration->sortiDetails),
                $contenu->personnageMort ? 'oui' : 'non',
                self::texte($contenu->circonstancesMort),
                self::libelles($contenu->sorts),
                self::libelles($contenu->prieres),
                self::libelles($contenu->potions),
                self::libelles($contenu->langues),
                self::libelles($contenu->connaissances),
                self::libelles($contenu->technologies),
                self::texte($contenu->competenceApprise?->getLabel()),
                $contenu->enseignantNumero ?? '',
                self::texte($declaration->eleves),
                self::texte($declaration->constructions),
                self::texte($declaration->renommee),
                $declaration->renommeePoints ?? '',
                self::texte($declaration->precisions),
                self::texte($declaration->autres),
                self::texte($contenu->introuvables),
            ];
        }

        return $avecEtatCivil ? $this->ajouterEtatCivil($lignes, $noms) : $lignes;
    }

    /**
     * Insère la colonne « Joueur (état civil) » après « Personnage ». Réservée aux rôles de gestion (voir le contrôleur).
     *
     * @param list<list<string|int>> $lignes
     * @param list<string> $noms
     *
     * @return list<list<string|int>>
     */
    private function ajouterEtatCivil(array $lignes, array $noms): array
    {
        foreach ($lignes as $i => $ligne) {
            array_splice($ligne, offset: 2, length: 0, replacement: [0 === $i ? 'Joueur (état civil)' : $noms[$i - 1] ?? '']);
            $lignes[$i] = $ligne;
        }

        return $lignes;
    }

    /**
     * @param list<list<string|int>> $lignes
     */
    public function csv(array $lignes): string
    {
        $flux = fopen('php://temp', 'r+');
        if (false === $flux) {
            return '';
        }

        fwrite($flux, "\xEF\xBB\xBF");
        foreach ($lignes as $ligne) {
            fputcsv($flux, $ligne, ',', '"', '');
        }

        rewind($flux);
        $contenu = (string) stream_get_contents($flux);
        fclose($flux);

        return $contenu;
    }

    /**
     * Texte libre sûr pour un tableur : une cellule qui commence par un caractère de formule est préfixée d'une apostrophe.
     */
    public static function texte(?string $valeur): string
    {
        $valeur = null === $valeur ? '' : trim($valeur);

        return 1 === preg_match('/^[=+\-@\t\r]/', $valeur) ? "'" . $valeur : $valeur;
    }

    /**
     * @param list<object> $entites
     */
    private static function libelles(array $entites): string
    {
        $libelles = [];
        foreach ($entites as $entite) {
            $libelles[] = method_exists($entite, 'getLabel') ? (string) $entite->getLabel() : '';
        }

        return self::texte(implode(' | ', $libelles));
    }

    /**
     * @return list<Depouillement>
     */
    private function dossiers(Gn $gn, DepouillementType $type): array
    {
        /** @var list<Depouillement> $dossiers */
        $dossiers = array_values($this->depouillementRepository->findBy(['gn' => $gn, 'type' => $type, 'simulation' => false], ['numero' => 'ASC']));

        return $dossiers;
    }
}
