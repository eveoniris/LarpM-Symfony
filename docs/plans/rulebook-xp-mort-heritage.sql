-- Rulebook : gains d'XP, XP à la mort, héritier (règles fournies le 2026-11-19).
-- Idempotent : relançable sans doublon (clé : code de la règle, puis règle + version pour la révision).
-- À jouer en local puis en production, par exemple :
--   mysql --default-character-set=utf8mb4 -u<user> -p <base> < docs/plans/rulebook-xp-mort-heritage.sql
-- Les règles sont créées non publiées (publiee = 0) ; la publication se fait depuis l'interface du rulebook.
-- Statuts : a_confirmer = règle énoncée, à valider ; todo = règle décidée, pas encore codée.

START TRANSACTION;

-- XP-03 Participation
INSERT INTO rulebook_regle (domaine_id, code, titre, position, texte_clair, detail_technique, statut, tests_refs, publiee, version, created_at, updated_at)
SELECT d.id, 'XP-03', 'XP de participation', 30,
  'Chaque joueur reçoit 2 XP de participation par opus, de façon automatique.',
  'Référence : `GnRepository::giveParticipationXp()`, lancée par la commande `app:gn:participation-xp` (le montant est un argument, 2 pour un opus). Idempotent : un participant qui a déjà le gain du même libellé n''est pas servi deux fois.\n\nÀ confirmer : la commande est aujourd''hui lancée à la main par l''équipe ; « automatique » signifie-t-il un déclenchement par le verrouillage du GN ?',
  'a_confirmer', '[]', 0, 1, NOW(), NOW()
FROM rulebook_domaine d
WHERE d.code = 'xp' AND NOT EXISTS (SELECT 1 FROM rulebook_regle r WHERE r.code = 'XP-03');

-- XP-04 Gains à discrétion et exceptionnels
INSERT INTO rulebook_regle (domaine_id, code, titre, position, texte_clair, detail_technique, statut, tests_refs, publiee, version, created_at, updated_at)
SELECT d.id, 'XP-04', 'Gains à la discrétion du scénariste et gains exceptionnels', 40,
  'Le scénariste peut accorder 3 XP à sa discrétion, dans la limite de 5 XP entre deux opus, et un apprentissage par période LH (acquis sur un opus principal, en bonus possible d''un inter-jeu). Des gains exceptionnels sont possibles hors inter-jeu, par exemple pour l''aide à un week-end de travail ou un RP exceptionnel.',
  'Saisie unitaire : `PersonnageController::adminXpAction` (route `/{personnage}/xp`). Saisie en masse : `/admin/masse` (`AjoutMasseService`). Retrait : `/admin/xp/gain` (`RetraitXpService`, suppression logique ou définitive).\n\nAucun plafond n''est contrôlé par le code (ni les 3 XP, ni les 5 XP entre opus).\n\nÀ confirmer : le plafond de 5 XP englobe-t-il les 2 XP de participation, ou ne vise-t-il que les gains à discrétion ?',
  'todo', '["tests/Functional/Admin/AjoutMasseTest.php","tests/Functional/Admin/RetraitXpTest.php"]', 0, 1, NOW(), NOW()
FROM rulebook_domaine d
WHERE d.code = 'xp' AND NOT EXISTS (SELECT 1 FROM rulebook_regle r WHERE r.code = 'XP-04');

-- XP-05 Bonus d'XP à la mort
INSERT INTO rulebook_regle (domaine_id, code, titre, position, texte_clair, detail_technique, statut, tests_refs, publiee, version, created_at, updated_at)
SELECT d.id, 'XP-05', 'Bonus d''XP à la création après une mort', 50,
  'Le personnage qu''un joueur recrée après la mort du précédent bénéficie d''un bonus d''expérience à la création égal au tiers des points d''expérience acquis pendant le jeu par le défunt, arrondi au supérieur. L''XP de création et le bonus lié à l''âge ne comptent pas. Exemple : un personnage créé à 17 XP qui meurt à 25 XP lègue (25 - 17) / 3 = 2,67, soit 3 XP.',
  'Formule : `bonus = ceil((xp_total_defunt - xp_creation - xp_bonus_age) / 3)`.\n\nBase de calcul : `Personnage::getXpTotal()` (somme des gains hors « Suppression de la compétence »), à diminuer des gains « Création de votre personnage » et « Bonus lié à l''age » (`PersonnageService`).\n\nNon codé : aucune règle de transmission dans le code. Un report par mort et par nouveau personnage (historique à créer). Voir le plan « report d''XP à la mort ».\n\nÀ confirmer : les XP post mortem donnés par `/admin/masse`, les gains d''inter-jeu et « Naissance de l''héritier » entrent-ils dans la base ?',
  'todo', '[]', 0, 1, NOW(), NOW()
FROM rulebook_domaine d
WHERE d.code = 'xp' AND NOT EXISTS (SELECT 1 FROM rulebook_regle r WHERE r.code = 'XP-05');

-- XP-06 Héritier
INSERT INTO rulebook_regle (domaine_id, code, titre, position, texte_clair, detail_technique, statut, tests_refs, publiee, version, created_at, updated_at)
SELECT d.id, 'XP-06', 'Bonus d''XP quand le joueur joue l''héritier', 60,
  'Si le défunt a un héritier en âge d''être joué (au moins 3 GN complets passés après la réussite de sa conception en jeu) et que le joueur le joue, le bonus est de la moitié, et non du tiers, de l''expérience acquise en jeu par le défunt. Exemple : pour 8 XP acquis, 4 XP au lieu de 3.',
  'Formule : `bonus = ceil((xp_total_defunt - xp_creation - xp_bonus_age) / 2)`.\n\nL''héritier est créé par `HeritierService` (gain « Naissance de l''héritier »). Condition d''âge : au moins 3 GN complets après la réussite de la conception (`ConceptionService`).\n\nNon codé : voir XP-05.',
  'todo', '[]', 0, 1, NOW(), NOW()
FROM rulebook_domaine d
WHERE d.code = 'xp' AND NOT EXISTS (SELECT 1 FROM rulebook_regle r WHERE r.code = 'XP-06');

-- XP-07 Changement de personnage sans mort
INSERT INTO rulebook_regle (domaine_id, code, titre, position, texte_clair, detail_technique, statut, tests_refs, publiee, version, created_at, updated_at)
SELECT d.id, 'XP-07', 'Changement de personnage d''un opus à l''autre', 70,
  'Un joueur peut changer de personnage d''un opus à l''autre sans que le précédent soit mort. Soit il décide que le personnage est mort après le LH : le scénariste le « tue » sur LarpManager et le nouveau personnage reçoit le bonus de XP-05 (ou XP-06 si héritier). Soit le personnage est mis en suspens : le nouveau personnage n''a aucun bonus, qui n''est obtenu que lorsque le premier personnage meurt (en jeu, ou de vieillesse s''il est rejoué). L''ancien personnage peut être repris tant qu''il n''est pas mort.',
  'Changement de statut : route `personnage.statut` (`PersonnageController`). Le statut « en suspens » et le lien entre un personnage mort et son bonus restent à créer.\n\nÀ confirmer : existence d''un statut « en suspens » dans l''application.',
  'todo', '[]', 0, 1, NOW(), NOW()
FROM rulebook_domaine d
WHERE d.code = 'xp' AND NOT EXISTS (SELECT 1 FROM rulebook_regle r WHERE r.code = 'XP-07');

-- Révision initiale de chaque règle ajoutée (historique du rulebook)
INSERT INTO rulebook_revision (regle_id, version, titre, texte_clair, detail_technique, statut, publiee, motif, auteur_id, created_at)
SELECT r.id, r.version, r.titre, r.texte_clair, r.detail_technique, r.statut, r.publiee, 'Ajout des règles d''XP, de mort et d''héritier (2026-11-19)', NULL, NOW()
FROM rulebook_regle r
WHERE r.code IN ('XP-03', 'XP-04', 'XP-05', 'XP-06', 'XP-07')
  AND NOT EXISTS (SELECT 1 FROM rulebook_revision v WHERE v.regle_id = r.id AND v.version = r.version);

COMMIT;
