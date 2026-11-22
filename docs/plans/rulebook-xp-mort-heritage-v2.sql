-- Rulebook : cadrage du report d'XP à la mort (décisions du 2026-11-22), règles XP-05 à XP-07.
-- À jouer après rulebook-xp-mort-heritage.sql. Idempotent : ne touche que les règles encore en version 1.
-- Statut a_confirmer = décidé, en attente de code ; la publication se fait depuis l'interface du rulebook.

START TRANSACTION;

UPDATE rulebook_regle SET version = 2, statut = 'todo', updated_at = NOW(),
  titre = 'Bonus d''XP à la création après une mort',
  texte_clair = 'Le personnage qu''un joueur recrée après la mort du précédent bénéficie d''un bonus d''expérience égal au tiers des points d''expérience acquis pendant le jeu par le défunt, arrondi au supérieur. L''XP de création, le bonus lié à l''âge, la naissance d''un héritier et les bonus déjà reçus ne comptent pas. Exemple : un personnage créé à 17 XP qui meurt à 25 XP lègue 8 / 3 = 2,67, soit 3 XP. Un mort ne transmet son bonus qu''une fois, et un personnage ne le reçoit qu''une fois. Le bonus se récupère sur un personnage qui n''a pas encore participé à un GN (inscrit à un GN dont la date de début est dans le futur : autorisé), à la création ou ensuite.',
  detail_technique = 'Formule : `bonus = intdiv(base + 2, 3)` avec `base = max(0, XP des gains - création - bonus d''âge - naissance de l''héritier - reports reçus - suppressions de compétence)` (gains retirés logiquement exclus). Service : `XpReportCalculateur`.\n\nXP post mortem (`/admin/masse`), inter-jeu et week-end de travail comptent dans la base.\n\nLe joueur récupère seul le tiers ; si le prochain personnage a déjà participé, le bonus reste disponible pour un nouveau personnage, et le scénariste peut forcer l''ajout. Un report par mort et par bénéficiaire (table `xp_report`, à créer).'
WHERE code = 'XP-05' AND version = 1;

UPDATE rulebook_regle SET version = 2, statut = 'todo', updated_at = NOW(),
  texte_clair = 'Si le défunt a un héritier en âge d''être joué et que le joueur le joue, le bonus est de la moitié, et non du tiers, de l''expérience acquise en jeu par le défunt. Exemple : pour 8 XP acquis, 4 XP au lieu de 3. Seul un scénariste peut indiquer un héritage et choisir l''héritier. Un héritier ne reçoit qu''un seul bonus : si ses deux parents sont morts, le scénariste choisit le parent qui le transmet (en général celui du même joueur).',
  detail_technique = 'Formule : `bonus = intdiv(base + 1, 2)` (même base que XP-05).\n\nL''héritier est créé par `HeritierService` (gain « Naissance de l''héritier », exclu de la base). Condition d''âge : avoir connu 3 GN principaux après la conception ; elle sert à vérifier que l''âge de l''héritier permet de l''interpréter, donc simple avertissement (motif obligatoire pour forcer), pas un blocage. Les GN étant espacés d''environ 5 ans, 3 GN principaux passés suffisent en pratique.\n\nLe lien d''affiliation est `PersonnageLignee`.'
WHERE code = 'XP-06' AND version = 1;

UPDATE rulebook_regle SET version = 2, statut = 'todo', updated_at = NOW(),
  texte_clair = 'Un joueur peut changer de personnage d''un opus à l''autre sans que le précédent soit mort. Soit le personnage est mort après le LH : le scénariste le « tue » sur LarpManager et le nouveau personnage reçoit le bonus de XP-05 (ou XP-06 si héritier). Soit le personnage reste vivant : le nouveau n''a aucun bonus, qui n''est obtenu que lorsque le premier meurt. L''ancien personnage peut être repris tant qu''il n''est pas mort. Un mort dont le bonus a été transmis ne peut plus revenir à la vie, sauf décision d''un administrateur qui retire alors le bonus transmis, après avertissement.',
  detail_technique = 'Aucun statut « en suspens » : un personnage vivant n''est simplement pas éligible, le bonus reste disponible tant que le report n''est pas consommé.\n\nChangement de statut : route `personnage.statut` (`PersonnageController`) ; à compléter par un contrôle et une alerte si l''on tente de ressusciter un mort dont le bonus est reporté (admin seulement, retrait du bonus via `RetraitXpService`).'
WHERE code = 'XP-07' AND version = 1;

INSERT INTO rulebook_revision (regle_id, version, titre, texte_clair, detail_technique, statut, publiee, motif, auteur_id, created_at)
SELECT r.id, r.version, r.titre, r.texte_clair, r.detail_technique, r.statut, r.publiee, 'Cadrage du report d''XP à la mort (2026-11-22)', NULL, NOW()
FROM rulebook_regle r
WHERE r.code IN ('XP-05', 'XP-06', 'XP-07') AND r.version = 2
  AND NOT EXISTS (SELECT 1 FROM rulebook_revision v WHERE v.regle_id = r.id AND v.version = r.version);

COMMIT;
