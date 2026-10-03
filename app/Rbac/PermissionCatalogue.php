<?php

namespace App\Rbac;

/**
 * Source de vérité du catalogue de permissions.
 *
 * Transcription des matrices du § 5 de ROLES_ET_PERMISSIONS.md. Les permissions des
 * fonctionnalités du § 6 seront ajoutées par les tâches qui les livrent, jamais ici
 * par anticipation : une permission sans point d'application est une permission morte.
 *
 * `is_sensitive` marque les actions dont la journalisation est obligatoire (§ 7.3 du
 * document de référence). Le document en compte quatorze ; la liste en porte quinze
 * parce que `finance.*.delete` y couvre la recette et la dépense.
 */
class PermissionCatalogue
{
    /**
     * @return list<array{name: string, domaine: string, label: string, scope: string, is_sensitive?: bool}>
     */
    public static function toutes(): array
    {
        return [
            // ── platform ──────────────────────────────────────────────────────
            ['name' => 'platform.pays.manage', 'domaine' => 'platform', 'label' => 'Gérer les pays', 'scope' => 'platform'],
            ['name' => 'platform.region.manage', 'domaine' => 'platform', 'label' => 'Gérer les régions', 'scope' => 'platform'],
            ['name' => 'platform.ville.manage', 'domaine' => 'platform', 'label' => 'Gérer les villes', 'scope' => 'platform'],
            ['name' => 'platform.compagnie.view', 'domaine' => 'platform', 'label' => 'Consulter les compagnies', 'scope' => 'platform'],
            ['name' => 'platform.compagnie.create', 'domaine' => 'platform', 'label' => 'Créer une compagnie', 'scope' => 'platform'],
            ['name' => 'platform.compagnie.update', 'domaine' => 'platform', 'label' => 'Modifier une compagnie', 'scope' => 'platform'],
            ['name' => 'platform.compagnie.activate', 'domaine' => 'platform', 'label' => 'Activer une compagnie', 'scope' => 'platform'],
            ['name' => 'platform.compagnie.suspend', 'domaine' => 'platform', 'label' => 'Suspendre une compagnie', 'scope' => 'platform', 'is_sensitive' => true],
            ['name' => 'platform.compagnie.delete', 'domaine' => 'platform', 'label' => 'Supprimer une compagnie', 'scope' => 'platform'],
            ['name' => 'platform.settings.manage', 'domaine' => 'platform', 'label' => 'Gérer les paramètres de la plateforme', 'scope' => 'platform'],
            ['name' => 'platform.user.view', 'domaine' => 'platform', 'label' => 'Consulter les comptes', 'scope' => 'platform'],
            ['name' => 'platform.user.update', 'domaine' => 'platform', 'label' => 'Modifier un compte', 'scope' => 'platform'],
            ['name' => 'platform.user.suspend', 'domaine' => 'platform', 'label' => 'Suspendre un compte', 'scope' => 'platform'],
            ['name' => 'platform.user.impersonate', 'domaine' => 'platform', 'label' => 'Usurper l\'identité d\'un compte', 'scope' => 'platform', 'is_sensitive' => true],
            ['name' => 'platform.stats.view', 'domaine' => 'platform', 'label' => 'Consulter les statistiques de la plateforme', 'scope' => 'platform'],
            ['name' => 'platform.audit.view', 'domaine' => 'platform', 'label' => 'Consulter la piste d\'audit de la plateforme', 'scope' => 'platform'],

            // ── compagnie ─────────────────────────────────────────────────────
            ['name' => 'compagnie.dashboard.view', 'domaine' => 'compagnie', 'label' => 'Accéder au tableau de bord', 'scope' => 'compagnie'],
            ['name' => 'compagnie.profil.view', 'domaine' => 'compagnie', 'label' => 'Consulter le profil de la compagnie', 'scope' => 'compagnie'],
            ['name' => 'compagnie.profil.update', 'domaine' => 'compagnie', 'label' => 'Modifier le profil de la compagnie', 'scope' => 'compagnie'],
            ['name' => 'compagnie.parametres.view', 'domaine' => 'compagnie', 'label' => 'Consulter les paramètres', 'scope' => 'compagnie'],
            ['name' => 'compagnie.parametres.update', 'domaine' => 'compagnie', 'label' => 'Modifier les paramètres', 'scope' => 'compagnie'],
            ['name' => 'compagnie.parametres.updateAdvanced', 'domaine' => 'compagnie', 'label' => 'Modifier les paramètres sensibles', 'scope' => 'compagnie', 'is_sensitive' => true],
            ['name' => 'compagnie.parametres.reset', 'domaine' => 'compagnie', 'label' => 'Réinitialiser les paramètres', 'scope' => 'compagnie'],
            ['name' => 'compagnie.user.view', 'domaine' => 'compagnie', 'label' => 'Consulter l\'équipe', 'scope' => 'compagnie'],
            ['name' => 'compagnie.user.create', 'domaine' => 'compagnie', 'label' => 'Créer un compte collaborateur', 'scope' => 'compagnie'],
            ['name' => 'compagnie.user.update', 'domaine' => 'compagnie', 'label' => 'Modifier un collaborateur', 'scope' => 'compagnie'],
            ['name' => 'compagnie.user.disable', 'domaine' => 'compagnie', 'label' => 'Désactiver un collaborateur', 'scope' => 'compagnie'],
            ['name' => 'compagnie.user.resetPassword', 'domaine' => 'compagnie', 'label' => 'Réinitialiser le mot de passe d\'un collaborateur', 'scope' => 'compagnie'],
            ['name' => 'compagnie.role.assign', 'domaine' => 'compagnie', 'label' => 'Attribuer des rôles', 'scope' => 'compagnie', 'is_sensitive' => true],
            ['name' => 'compagnie.role.manage', 'domaine' => 'compagnie', 'label' => 'Gérer les rôles de la compagnie', 'scope' => 'compagnie'],
            ['name' => 'compagnie.gare.assignUser', 'domaine' => 'compagnie', 'label' => 'Affecter un collaborateur à une gare', 'scope' => 'compagnie'],

            // ── reseau ────────────────────────────────────────────────────────
            ['name' => 'reseau.gare.view', 'domaine' => 'reseau', 'label' => 'Consulter les gares', 'scope' => 'compagnie'],
            ['name' => 'reseau.gare.create', 'domaine' => 'reseau', 'label' => 'Créer une gare', 'scope' => 'compagnie'],
            ['name' => 'reseau.gare.update', 'domaine' => 'reseau', 'label' => 'Modifier une gare', 'scope' => 'compagnie'],
            ['name' => 'reseau.gare.delete', 'domaine' => 'reseau', 'label' => 'Supprimer une gare', 'scope' => 'compagnie'],
            ['name' => 'reseau.gare.setDefault', 'domaine' => 'reseau', 'label' => 'Définir la gare par défaut', 'scope' => 'compagnie'],
            ['name' => 'reseau.vehicule.view', 'domaine' => 'reseau', 'label' => 'Consulter les véhicules', 'scope' => 'compagnie'],
            ['name' => 'reseau.vehicule.create', 'domaine' => 'reseau', 'label' => 'Ajouter un véhicule', 'scope' => 'compagnie'],
            ['name' => 'reseau.vehicule.update', 'domaine' => 'reseau', 'label' => 'Modifier un véhicule', 'scope' => 'compagnie'],
            ['name' => 'reseau.vehicule.delete', 'domaine' => 'reseau', 'label' => 'Supprimer un véhicule', 'scope' => 'compagnie'],
            ['name' => 'reseau.vehicule.setStatut', 'domaine' => 'reseau', 'label' => 'Changer le statut d\'un véhicule', 'scope' => 'compagnie'],
            ['name' => 'reseau.chauffeur.view', 'domaine' => 'reseau', 'label' => 'Consulter les chauffeurs', 'scope' => 'compagnie'],
            ['name' => 'reseau.chauffeur.create', 'domaine' => 'reseau', 'label' => 'Ajouter un chauffeur', 'scope' => 'compagnie'],
            ['name' => 'reseau.chauffeur.update', 'domaine' => 'reseau', 'label' => 'Modifier un chauffeur', 'scope' => 'compagnie'],
            ['name' => 'reseau.chauffeur.delete', 'domaine' => 'reseau', 'label' => 'Supprimer un chauffeur', 'scope' => 'compagnie'],
            ['name' => 'reseau.document.view', 'domaine' => 'reseau', 'label' => 'Consulter les documents', 'scope' => 'compagnie'],
            ['name' => 'reseau.document.upload', 'domaine' => 'reseau', 'label' => 'Déposer un document', 'scope' => 'compagnie'],
            ['name' => 'reseau.document.delete', 'domaine' => 'reseau', 'label' => 'Supprimer un document', 'scope' => 'compagnie'],
            ['name' => 'reseau.document.manageRappel', 'domaine' => 'reseau', 'label' => 'Gérer les rappels d\'échéance', 'scope' => 'compagnie'],

            // ── voyage ────────────────────────────────────────────────────────
            ['name' => 'voyage.trajet.view', 'domaine' => 'voyage', 'label' => 'Consulter les trajets', 'scope' => 'compagnie'],
            ['name' => 'voyage.trajet.create', 'domaine' => 'voyage', 'label' => 'Créer un trajet', 'scope' => 'compagnie'],
            ['name' => 'voyage.trajet.update', 'domaine' => 'voyage', 'label' => 'Modifier un trajet', 'scope' => 'compagnie'],
            ['name' => 'voyage.trajet.delete', 'domaine' => 'voyage', 'label' => 'Supprimer un trajet', 'scope' => 'compagnie'],
            ['name' => 'voyage.classe.manage', 'domaine' => 'voyage', 'label' => 'Gérer les classes', 'scope' => 'compagnie'],
            ['name' => 'voyage.confort.manage', 'domaine' => 'voyage', 'label' => 'Gérer les conforts', 'scope' => 'compagnie'],
            ['name' => 'voyage.voyage.view', 'domaine' => 'voyage', 'label' => 'Consulter les voyages', 'scope' => 'compagnie'],
            ['name' => 'voyage.voyage.create', 'domaine' => 'voyage', 'label' => 'Créer un voyage', 'scope' => 'compagnie'],
            ['name' => 'voyage.voyage.update', 'domaine' => 'voyage', 'label' => 'Modifier un voyage', 'scope' => 'compagnie'],
            ['name' => 'voyage.voyage.delete', 'domaine' => 'voyage', 'label' => 'Supprimer un voyage', 'scope' => 'compagnie'],
            ['name' => 'voyage.voyage.publish', 'domaine' => 'voyage', 'label' => 'Publier un voyage', 'scope' => 'compagnie'],
            ['name' => 'voyage.tarif.update', 'domaine' => 'voyage', 'label' => 'Modifier un tarif', 'scope' => 'compagnie'],
            ['name' => 'voyage.instance.view', 'domaine' => 'voyage', 'label' => 'Consulter les départs programmés', 'scope' => 'compagnie'],
            ['name' => 'voyage.instance.generate', 'domaine' => 'voyage', 'label' => 'Générer les départs', 'scope' => 'compagnie'],
            ['name' => 'voyage.instance.update', 'domaine' => 'voyage', 'label' => 'Modifier un départ', 'scope' => 'compagnie'],
            ['name' => 'voyage.instance.assignVehicule', 'domaine' => 'voyage', 'label' => 'Affecter un véhicule à un départ', 'scope' => 'compagnie'],
            ['name' => 'voyage.instance.assignChauffeur', 'domaine' => 'voyage', 'label' => 'Affecter un chauffeur à un départ', 'scope' => 'compagnie'],
            ['name' => 'voyage.instance.cancel', 'domaine' => 'voyage', 'label' => 'Annuler un départ', 'scope' => 'compagnie'],
            ['name' => 'voyage.instance.close', 'domaine' => 'voyage', 'label' => 'Clôturer un départ', 'scope' => 'compagnie'],

            // ── guichet ───────────────────────────────────────────────────────
            ['name' => 'guichet.ticket.view.gare', 'domaine' => 'guichet', 'label' => 'Consulter les tickets de sa gare', 'scope' => 'compagnie'],
            ['name' => 'guichet.ticket.view.all', 'domaine' => 'guichet', 'label' => 'Consulter tous les tickets', 'scope' => 'compagnie'],
            ['name' => 'guichet.ticket.sell', 'domaine' => 'guichet', 'label' => 'Vendre un ticket', 'scope' => 'compagnie'],
            ['name' => 'guichet.ticket.print', 'domaine' => 'guichet', 'label' => 'Imprimer un ticket', 'scope' => 'compagnie'],
            ['name' => 'guichet.ticket.reprint', 'domaine' => 'guichet', 'label' => 'Réimprimer un ticket', 'scope' => 'compagnie', 'is_sensitive' => true],
            ['name' => 'guichet.ticket.changeDate', 'domaine' => 'guichet', 'label' => 'Changer la date d\'un ticket', 'scope' => 'compagnie'],
            ['name' => 'guichet.ticket.transfer', 'domaine' => 'guichet', 'label' => 'Transférer un ticket', 'scope' => 'compagnie'],
            ['name' => 'guichet.ticket.pause', 'domaine' => 'guichet', 'label' => 'Mettre un ticket en pause', 'scope' => 'compagnie'],
            ['name' => 'guichet.ticket.cancel', 'domaine' => 'guichet', 'label' => 'Annuler un ticket', 'scope' => 'compagnie', 'is_sensitive' => true],
            ['name' => 'guichet.ticket.block', 'domaine' => 'guichet', 'label' => 'Bloquer un ticket', 'scope' => 'compagnie'],
            ['name' => 'guichet.ticket.unblock', 'domaine' => 'guichet', 'label' => 'Débloquer un ticket', 'scope' => 'compagnie', 'is_sensitive' => true],
            ['name' => 'guichet.ticket.export', 'domaine' => 'guichet', 'label' => 'Exporter les tickets', 'scope' => 'compagnie'],
            ['name' => 'guichet.siege.reassign', 'domaine' => 'guichet', 'label' => 'Réattribuer un siège', 'scope' => 'compagnie'],
            ['name' => 'guichet.reservation.hold', 'domaine' => 'guichet', 'label' => 'Poser une réservation', 'scope' => 'compagnie'],

            // ── embarquement ──────────────────────────────────────────────────
            ['name' => 'embarquement.app.login', 'domaine' => 'embarquement', 'label' => 'Se connecter à l\'application agent', 'scope' => 'compagnie'],
            ['name' => 'embarquement.manifeste.view', 'domaine' => 'embarquement', 'label' => 'Consulter le manifeste', 'scope' => 'compagnie'],
            ['name' => 'embarquement.ticket.scan', 'domaine' => 'embarquement', 'label' => 'Scanner un ticket', 'scope' => 'compagnie'],
            ['name' => 'embarquement.ticket.validate', 'domaine' => 'embarquement', 'label' => 'Valider un ticket', 'scope' => 'compagnie'],
            ['name' => 'embarquement.ticket.verifyByPhone', 'domaine' => 'embarquement', 'label' => 'Vérifier un ticket par téléphone', 'scope' => 'compagnie'],
            ['name' => 'embarquement.ticket.markAbsent', 'domaine' => 'embarquement', 'label' => 'Marquer un passager absent', 'scope' => 'compagnie'],
            ['name' => 'embarquement.ticket.block', 'domaine' => 'embarquement', 'label' => 'Bloquer un ticket à l\'embarquement', 'scope' => 'compagnie'],
            ['name' => 'embarquement.bagage.register', 'domaine' => 'embarquement', 'label' => 'Enregistrer un bagage', 'scope' => 'compagnie'],
            ['name' => 'embarquement.sync.pull', 'domaine' => 'embarquement', 'label' => 'Télécharger les données hors ligne', 'scope' => 'compagnie'],
            ['name' => 'embarquement.sync.push', 'domaine' => 'embarquement', 'label' => 'Remonter les actions hors ligne', 'scope' => 'compagnie'],
            ['name' => 'embarquement.conflit.view', 'domaine' => 'embarquement', 'label' => 'Consulter les conflits d\'embarquement', 'scope' => 'compagnie'],
            ['name' => 'embarquement.conflit.resolve', 'domaine' => 'embarquement', 'label' => 'Arbitrer un conflit d\'embarquement', 'scope' => 'compagnie', 'is_sensitive' => true],

            // ── caisse ────────────────────────────────────────────────────────
            ['name' => 'caisse.session.open', 'domaine' => 'caisse', 'label' => 'Ouvrir une session de caisse', 'scope' => 'compagnie'],
            ['name' => 'caisse.session.close', 'domaine' => 'caisse', 'label' => 'Clôturer une session de caisse', 'scope' => 'compagnie'],
            ['name' => 'caisse.session.view.own', 'domaine' => 'caisse', 'label' => 'Consulter sa session de caisse', 'scope' => 'compagnie'],
            ['name' => 'caisse.session.view.gare', 'domaine' => 'caisse', 'label' => 'Consulter les caisses de sa gare', 'scope' => 'compagnie'],
            ['name' => 'caisse.session.view.all', 'domaine' => 'caisse', 'label' => 'Consulter toutes les caisses', 'scope' => 'compagnie'],
            ['name' => 'caisse.session.forceClose', 'domaine' => 'caisse', 'label' => 'Clôturer de force une session', 'scope' => 'compagnie', 'is_sensitive' => true],
            ['name' => 'caisse.ecart.justify', 'domaine' => 'caisse', 'label' => 'Justifier un écart de caisse', 'scope' => 'compagnie'],
            ['name' => 'caisse.ecart.validate', 'domaine' => 'caisse', 'label' => 'Valider un écart de caisse', 'scope' => 'compagnie', 'is_sensitive' => true],
            ['name' => 'caisse.historique.view', 'domaine' => 'caisse', 'label' => 'Consulter l\'historique des caisses', 'scope' => 'compagnie'],
            ['name' => 'caisse.historique.export', 'domaine' => 'caisse', 'label' => 'Exporter l\'historique des caisses', 'scope' => 'compagnie'],

            // ── finance ───────────────────────────────────────────────────────
            ['name' => 'finance.bilan.view', 'domaine' => 'finance', 'label' => 'Consulter le bilan financier', 'scope' => 'compagnie'],
            ['name' => 'finance.recette.view', 'domaine' => 'finance', 'label' => 'Consulter les recettes', 'scope' => 'compagnie'],
            ['name' => 'finance.recette.create', 'domaine' => 'finance', 'label' => 'Enregistrer une recette', 'scope' => 'compagnie'],
            ['name' => 'finance.recette.update', 'domaine' => 'finance', 'label' => 'Modifier une recette', 'scope' => 'compagnie'],
            ['name' => 'finance.recette.delete', 'domaine' => 'finance', 'label' => 'Supprimer une recette', 'scope' => 'compagnie', 'is_sensitive' => true],
            ['name' => 'finance.depense.view', 'domaine' => 'finance', 'label' => 'Consulter les dépenses', 'scope' => 'compagnie'],
            ['name' => 'finance.depense.create', 'domaine' => 'finance', 'label' => 'Enregistrer une dépense', 'scope' => 'compagnie'],
            ['name' => 'finance.depense.update', 'domaine' => 'finance', 'label' => 'Modifier une dépense', 'scope' => 'compagnie'],
            ['name' => 'finance.depense.delete', 'domaine' => 'finance', 'label' => 'Supprimer une dépense', 'scope' => 'compagnie', 'is_sensitive' => true],
            ['name' => 'finance.depense.approve', 'domaine' => 'finance', 'label' => 'Approuver une dépense', 'scope' => 'compagnie', 'is_sensitive' => true],
            ['name' => 'finance.categorie.manage', 'domaine' => 'finance', 'label' => 'Gérer les catégories de dépense', 'scope' => 'compagnie'],
            ['name' => 'finance.promo.view', 'domaine' => 'finance', 'label' => 'Consulter les codes promo', 'scope' => 'compagnie'],
            ['name' => 'finance.promo.create', 'domaine' => 'finance', 'label' => 'Créer un code promo', 'scope' => 'compagnie'],
            ['name' => 'finance.promo.update', 'domaine' => 'finance', 'label' => 'Modifier un code promo', 'scope' => 'compagnie'],
            ['name' => 'finance.promo.deactivate', 'domaine' => 'finance', 'label' => 'Désactiver un code promo', 'scope' => 'compagnie'],
            ['name' => 'finance.rapport.view', 'domaine' => 'finance', 'label' => 'Consulter les rapports', 'scope' => 'compagnie'],
            ['name' => 'finance.rapport.export', 'domaine' => 'finance', 'label' => 'Exporter les rapports', 'scope' => 'compagnie'],
            ['name' => 'finance.remboursement.request', 'domaine' => 'finance', 'label' => 'Demander un remboursement', 'scope' => 'compagnie'],
            ['name' => 'finance.remboursement.approve', 'domaine' => 'finance', 'label' => 'Approuver un remboursement', 'scope' => 'compagnie', 'is_sensitive' => true],

            // ── contenu ───────────────────────────────────────────────────────
            ['name' => 'contenu.article.view', 'domaine' => 'contenu', 'label' => 'Consulter les articles', 'scope' => 'compagnie'],
            ['name' => 'contenu.article.create', 'domaine' => 'contenu', 'label' => 'Rédiger un article', 'scope' => 'compagnie'],
            ['name' => 'contenu.article.update', 'domaine' => 'contenu', 'label' => 'Modifier un article', 'scope' => 'compagnie'],
            ['name' => 'contenu.article.publish', 'domaine' => 'contenu', 'label' => 'Publier un article', 'scope' => 'compagnie'],
            ['name' => 'contenu.article.delete', 'domaine' => 'contenu', 'label' => 'Supprimer un article', 'scope' => 'compagnie'],
            ['name' => 'contenu.categorie.manage', 'domaine' => 'contenu', 'label' => 'Gérer les catégories d\'article', 'scope' => 'compagnie'],
            ['name' => 'contenu.tag.manage', 'domaine' => 'contenu', 'label' => 'Gérer les étiquettes', 'scope' => 'compagnie'],
            ['name' => 'contenu.commentaire.moderate', 'domaine' => 'contenu', 'label' => 'Modérer les commentaires', 'scope' => 'compagnie'],

            // ── crm ───────────────────────────────────────────────────────────
            ['name' => 'crm.conversation.view', 'domaine' => 'crm', 'label' => 'Consulter les conversations', 'scope' => 'compagnie'],
            ['name' => 'crm.conversation.reply', 'domaine' => 'crm', 'label' => 'Répondre à une conversation', 'scope' => 'compagnie'],
            ['name' => 'crm.conversation.assign', 'domaine' => 'crm', 'label' => 'Assigner une conversation', 'scope' => 'compagnie'],
            ['name' => 'crm.conversation.close', 'domaine' => 'crm', 'label' => 'Clôturer une conversation', 'scope' => 'compagnie'],
            ['name' => 'crm.rating.view', 'domaine' => 'crm', 'label' => 'Consulter les avis', 'scope' => 'compagnie'],
            ['name' => 'crm.rating.reply', 'domaine' => 'crm', 'label' => 'Répondre à un avis', 'scope' => 'compagnie'],
            ['name' => 'crm.rating.report', 'domaine' => 'crm', 'label' => 'Signaler un avis', 'scope' => 'compagnie'],
            ['name' => 'crm.bugreport.view', 'domaine' => 'crm', 'label' => 'Consulter les signalements', 'scope' => 'compagnie'],
            ['name' => 'crm.bugreport.triage', 'domaine' => 'crm', 'label' => 'Trier les signalements', 'scope' => 'compagnie'],
            ['name' => 'crm.fidelite.view', 'domaine' => 'crm', 'label' => 'Consulter les points de fidélité', 'scope' => 'compagnie'],
            ['name' => 'crm.fidelite.adjust', 'domaine' => 'crm', 'label' => 'Ajuster les points de fidélité', 'scope' => 'compagnie', 'is_sensitive' => true],

            // ── client ────────────────────────────────────────────────────────
            ['name' => 'client.profil.view', 'domaine' => 'client', 'label' => 'Consulter son profil', 'scope' => 'client'],
            ['name' => 'client.profil.update', 'domaine' => 'client', 'label' => 'Modifier son profil', 'scope' => 'client'],
            ['name' => 'client.ticket.buy', 'domaine' => 'client', 'label' => 'Acheter un ticket', 'scope' => 'client'],
            ['name' => 'client.ticket.view.own', 'domaine' => 'client', 'label' => 'Consulter ses tickets', 'scope' => 'client'],
            ['name' => 'client.ticket.pdf', 'domaine' => 'client', 'label' => 'Télécharger son ticket en PDF', 'scope' => 'client'],
            ['name' => 'client.ticket.regenerate', 'domaine' => 'client', 'label' => 'Régénérer son ticket', 'scope' => 'client'],
            ['name' => 'client.ticket.transfer', 'domaine' => 'client', 'label' => 'Transférer son ticket', 'scope' => 'client'],
            ['name' => 'client.ticket.pause', 'domaine' => 'client', 'label' => 'Mettre son ticket en pause', 'scope' => 'client'],
            ['name' => 'client.ticket.changeDate', 'domaine' => 'client', 'label' => 'Changer la date de son ticket', 'scope' => 'client'],
            ['name' => 'client.ticket.buyForOther', 'domaine' => 'client', 'label' => 'Acheter pour un tiers', 'scope' => 'client'],
            ['name' => 'client.paiement.initiate', 'domaine' => 'client', 'label' => 'Initier un paiement', 'scope' => 'client'],
            ['name' => 'client.post.comment', 'domaine' => 'client', 'label' => 'Commenter un article', 'scope' => 'client'],
            ['name' => 'client.post.like', 'domaine' => 'client', 'label' => 'Aimer un article', 'scope' => 'client'],
            ['name' => 'client.rating.create', 'domaine' => 'client', 'label' => 'Déposer un avis', 'scope' => 'client'],
            ['name' => 'client.bugreport.create', 'domaine' => 'client', 'label' => 'Signaler un problème', 'scope' => 'client'],
            ['name' => 'client.conversation.create', 'domaine' => 'client', 'label' => 'Ouvrir une conversation', 'scope' => 'client'],
            ['name' => 'client.fidelite.view.own', 'domaine' => 'client', 'label' => 'Consulter ses points de fidélité', 'scope' => 'client'],
            ['name' => 'client.notification.manage', 'domaine' => 'client', 'label' => 'Gérer ses notifications', 'scope' => 'client'],
        ];
    }

    /** Noms de toutes les permissions du catalogue. */
    public static function noms(): array
    {
        return array_column(self::toutes(), 'name');
    }

    /** Permissions dont chaque usage doit laisser une trace d'audit. */
    public static function sensibles(): array
    {
        return array_column(
            array_filter(self::toutes(), fn (array $p): bool => $p['is_sensitive'] ?? false),
            'name'
        );
    }

    /** Permissions d'un domaine donné. */
    public static function duDomaine(string $domaine): array
    {
        return array_values(array_filter(
            self::toutes(),
            fn (array $p): bool => $p['domaine'] === $domaine
        ));
    }
}
