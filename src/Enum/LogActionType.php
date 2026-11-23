<?php

declare(strict_types=1);

namespace App\Enum;

enum LogActionType: string
{
    use EnumTraits;

    case ADD_COMPETENCE = 'add_competence';
    case ADD_CONNAISSANCE = 'add_connaissance';
    case REMOVE_CONNAISSANCE = 'remove_connaissance';
    case AGING_CHARACTERS = 'AgingCharacters';
    case ENTITY_UPDATE = 'entity_update';
    case ENTITY_DELETE = 'entity_delete';
    case ENTITY_ADD = 'entity_add';
    case XP_ADD = 'xp_add';
    case XP_USE = 'xp_use';

    /** Retrait d'un gain d'XP depuis l'historique (data : mode soft ou hard, motif, xp retiré). */
    case XP_RETRAIT = 'xp_retrait';

    /** Report du bonus d'XP d'un personnage mort vers un autre (data : mort, bénéficiaire, mode, montant). */
    case XP_REPORT = 'xp_report';

    /** Annulation d'un report d'XP à la mort (data : mort, bénéficiaire, montant retiré, motif). */
    case XP_REPORT_ANNULE = 'xp_report_annule';
    case ADD_DOMAINE_MAGIE = 'add_domaine_magie';
    case ADD_SORT = 'add_sort';
    case REMVOVE_SORT = 'remove_sort';
    case ADD_LANGUE = 'add_langue';
    case ADD_ORIGINE = 'add_origine';
    case ADD_POTION = 'add_potion';
    case REMOVE_POTION = 'remove_potion';
    case ADD_POTION_DEPART = 'add_potion_depart';
    case ADD_RELIGION = 'add_religion';
    case LOCK_RELIGION = 'lock_religion';
    case ADD_RENOMME = 'add_renomme';
    case DELETE_POTION_DEPART = 'delete_potion_depart';
    case ENTITY = 'entity';
    case OTHER = 'autre';
    case DUPLICATE_PERSONNAGE = 'duplicate_personnage';

    /** Validation d'un participant HelloAsso dans le rapprochement de la billetterie. */
    case BILLETTERIE_SYNC = 'billetterie_sync';

    /**
     * Validation ou renvoi d'un dépouillement d'enveloppe. Événement sommaire : les données et le détail sont dans
     * l'historique du dépouillement (DepouillementHistory), que `data` référence.
     */
    case DEPOUILLEMENT = 'depouillement';

    /** Verrouillage d'un groupe : bloque la modification de ses personnages. */
    case GROUPE_LOCK = 'groupe_lock';

    /** Déverrouillage d'un groupe. */
    case GROUPE_UNLOCK = 'groupe_unlock';

    /**
     * Modification faite par un scénariste sur un groupe, un participant ou un personnage sous verrouillage souple
     * (data : route, url, méthode, groupe). Non portée dans les enveloppes remises aux joueurs.
     */
    case MODIFICATION_SOUS_VERROU = 'modification_sous_verrou';

    /** Passage du verrouillage souple au verrouillage strict d'un GN, ou retour (data : gn, niveau). */
    case GN_VERROU_NIVEAU = 'gn_verrou_niveau';

    /**
     * Modification de la chaîne de personnages d'une participation : principal,
     * substitution, relève ou archétype de secours.
     */
    case PERSONNAGE_ROLE_CHANGE = 'personnage_role_change';

    /** Création, modification ou restauration d'une règle du rulebook (data : action, règle, version, motif). */
    case RULEBOOK = 'rulebook';

    /** Trigger de choix (potion, sort...) recréé par une commande de réparation. */
    case TRIGGER_REPARATION = 'trigger_reparation';

    /** Envoi de messages en masse par un admin (data : type d'ids, nombre de messages, titre, auteur). */
    case MESSAGE_MASSE = 'message_masse';

    /** Ajout en masse par un admin (data : type, valeur, motif, personnage_ids). */
    case AJOUT_MASSE = 'ajout_masse';

    /** Refus d'accès d'un utilisateur connecté à une ressource précise, du type incrément d'id dans l'URL (data : route, url, message). */
    case NO_ACCESS = 'no_access';
}
