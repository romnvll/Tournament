-- Correction de la contrainte pour permettre les trois NULL (message à tout le tournoi)

ALTER TABLE `Messages` DROP CONSTRAINT `chk_messages_une_seule_portee`;

ALTER TABLE `Messages` ADD CONSTRAINT `chk_messages_une_seule_portee` CHECK (
  (`categorie_id` IS NOT NULL AND `poule_id` IS NULL AND `equipe_id` IS NULL) OR
  (`categorie_id` IS NULL AND `poule_id` IS NOT NULL AND `equipe_id` IS NULL) OR
  (`categorie_id` IS NULL AND `poule_id` IS NULL AND `equipe_id` IS NOT NULL) OR
  (`categorie_id` IS NULL AND `poule_id` IS NULL AND `equipe_id` IS NULL)
);
