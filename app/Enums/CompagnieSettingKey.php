<?php

namespace App\Enums;

use App\DTOs\Compagnie\SettingDefinition;

/**
 * Catalogue des paramètres configurables par compagnie.
 *
 * Chaque case porte sa définition complète (type, groupe, défaut, validation),
 * ce qui fait de cet enum l'unique source de vérité du paramétrage :
 * les panels, l'API et les services métier en dérivent tous.
 */
enum CompagnieSettingKey: string
{
    // ── Général ─────────────────────────────────────────────────────────────
    case DEVISE                    = 'devise';
    case DEVISE_POSITION           = 'devise_position';
    case DEVISE_PRICE_TO_USD       = 'devise_price_to_usd';
    case FUSEAU_HORAIRE            = 'fuseau_horaire';
    case LANGUE                    = 'langue';
    case CONTACT_TELEPHONE         = 'contact_telephone';
    case CONTACT_EMAIL             = 'contact_email';
    case CONTACT_WHATSAPP          = 'contact_whatsapp';
    case ADRESSE_SIEGE             = 'adresse_siege';

    // ── Réservation ─────────────────────────────────────────────────────────
    case RESERVATION_EN_LIGNE      = 'reservation_en_ligne';
    case OUVERTURE_VENTE_JOURS     = 'ouverture_vente_jours';
    case CLOTURE_VENTE_MINUTES     = 'cloture_vente_minutes';
    case MAX_PLACES_PAR_RESERVATION = 'max_places_par_reservation';
    case PIECE_IDENTITE_OBLIGATOIRE = 'piece_identite_obligatoire';
    case SELECTION_SIEGE_ACTIVEE   = 'selection_siege_activee';
    case RESERVATION_POUR_AUTRUI   = 'reservation_pour_autrui';
    case BAGAGE_GRATUIT_KG         = 'bagage_gratuit_kg';
    case PRIX_KG_SUPPLEMENTAIRE    = 'prix_kg_supplementaire';

    // ── Annulation & modification ───────────────────────────────────────────
    case ANNULATION_AUTORISEE      = 'annulation_autorisee';
    case DELAI_ANNULATION          = 'delai_annulation';
    case PENALITE_ANNULATION       = 'penalite_annulation';
    case REMBOURSEMENT_AUTOMATIQUE = 'remboursement_automatique';
    case MODIFICATION_AUTORISEE    = 'modification_autorisee';
    case DELAI_MODIFICATION        = 'delai_modification';
    case FRAIS_MODIFICATION        = 'frais_modification';

    // ── Paiement ────────────────────────────────────────────────────────────
    case PAIEMENT_EN_LIGNE         = 'paiement_en_ligne';
    case PAIEMENT_ESPECE_GUICHET   = 'paiement_espece_guichet';
    case MOYENS_PAIEMENT_ACTIFS    = 'moyens_paiement_actifs';
    case EXPIRATION_PAIEMENT_MINUTES = 'expiration_paiement_minutes';
    case FRAIS_SERVICE             = 'frais_service';
    case FRAIS_SERVICE_TYPE        = 'frais_service_type';

    // ── Tickets ─────────────────────────────────────────────────────────────
    case QR_CODE_OBLIGATOIRE       = 'qr_code_obligatoire';
    case PREFIXE_NUMERO_TICKET     = 'prefixe_numero_ticket';
    case VALIDATION_AGENT_OBLIGATOIRE = 'validation_agent_obligatoire';
    case IMPRESSION_AUTO_APRES_VENTE = 'impression_auto_apres_vente';
    case MENTIONS_LEGALES_TICKET   = 'mentions_legales_ticket';

    // ── Notifications ───────────────────────────────────────────────────────
    case NOTIF_SMS_ACTIVE          = 'notif_sms_active';
    case NOTIF_EMAIL_ACTIVE        = 'notif_email_active';
    case NOTIF_PUSH_ACTIVE         = 'notif_push_active';
    case NOTIF_WHATSAPP_ACTIVE     = 'notif_whatsapp_active';
    case RAPPEL_DEPART_HEURES      = 'rappel_depart_heures';
    case SIGNATURE_SMS             = 'signature_sms';

    // ── Fidélité & promotions ───────────────────────────────────────────────
    case FIDELITE_ACTIVE           = 'fidelite_active';
    case POINTS_PAR_MONTANT        = 'points_par_montant';
    case CODES_PROMO_ACTIFS        = 'codes_promo_actifs';

    // ── Apparence ───────────────────────────────────────────────────────────
    case COULEUR_PRIMAIRE          = 'couleur_primaire';
    case COULEUR_SECONDAIRE        = 'couleur_secondaire';
    case AFFICHER_LOGO_TICKET      = 'afficher_logo_ticket';

    // ── Avancé (administrateurs plateforme) ─────────────────────────────────
    case COMMISSION_PLATEFORME     = 'commission_plateforme';
    case PLAFOND_VENTE_JOURNALIER  = 'plafond_vente_journalier';
    case MODE_MAINTENANCE          = 'mode_maintenance';
    case API_AGENT_ACTIVE          = 'api_agent_active';
    case DELAI_PAUSE_NON_CONSOMME  = 'delai_pause_non_consomme';

    public function definition(): SettingDefinition
    {
        return match ($this) {
            // ── Général ──────────────────────────────────────────────────────
            self::DEVISE => $this->make(
                CompagnieSettingType::Select, CompagnieSettingGroup::General,
                'Devise', 'XOF',
                help: 'Devise utilisée pour tous les montants affichés et facturés.',
                options: ['XOF' => 'Franc CFA (XOF)', 'XAF' => 'Franc CFA (XAF)', 'EUR' => 'Euro (EUR)', 'USD' => 'Dollar US (USD)', 'GHS' => 'Cedi (GHS)', 'NGN' => 'Naira (NGN)'],
                public: true,
            ),
            self::DEVISE_POSITION => $this->make(
                CompagnieSettingType::Select, CompagnieSettingGroup::General,
                'Position de la devise', 'right',
                help: 'Symbole affiché avant ou après le montant.',
                options: ['left' => 'Avant le montant (XOF 5 000)', 'right' => 'Après le montant (5 000 XOF)'],
                public: true,
            ),
            self::DEVISE_PRICE_TO_USD => $this->make(
                CompagnieSettingType::Float, CompagnieSettingGroup::General,
                'Taux de conversion vers l\'USD', 650.0,
                help: 'Nombre d\'unités de la devise pour 1 dollar US.',
                rules: ['min:0.0001'],
            ),
            self::FUSEAU_HORAIRE => $this->make(
                CompagnieSettingType::Select, CompagnieSettingGroup::General,
                'Fuseau horaire', 'Africa/Ouagadougou',
                help: 'Fuseau appliqué aux horaires de départ et d\'arrivée.',
                options: [
                    'Africa/Ouagadougou' => 'Ouagadougou (GMT+0)',
                    'Africa/Abidjan'     => 'Abidjan (GMT+0)',
                    'Africa/Bamako'      => 'Bamako (GMT+0)',
                    'Africa/Dakar'       => 'Dakar (GMT+0)',
                    'Africa/Accra'       => 'Accra (GMT+0)',
                    'Africa/Lome'        => 'Lomé (GMT+0)',
                    'Africa/Cotonou'     => 'Cotonou (GMT+1)',
                    'Africa/Niamey'      => 'Niamey (GMT+1)',
                    'Africa/Lagos'       => 'Lagos (GMT+1)',
                ],
                public: true,
            ),
            self::LANGUE => $this->make(
                CompagnieSettingType::Select, CompagnieSettingGroup::General,
                'Langue par défaut', 'fr',
                help: 'Langue des messages envoyés aux voyageurs.',
                options: ['fr' => 'Français', 'en' => 'Anglais'],
                public: true,
            ),
            self::CONTACT_TELEPHONE => $this->make(
                CompagnieSettingType::String, CompagnieSettingGroup::General,
                'Téléphone de contact', '',
                help: 'Numéro affiché aux voyageurs sur les tickets et dans l\'application.',
                rules: ['max:30'],
                public: true,
            ),
            self::CONTACT_EMAIL => $this->make(
                CompagnieSettingType::String, CompagnieSettingGroup::General,
                'Email de contact', '',
                rules: ['email', 'max:150'],
                public: true,
            ),
            self::CONTACT_WHATSAPP => $this->make(
                CompagnieSettingType::String, CompagnieSettingGroup::General,
                'WhatsApp', '',
                help: 'Numéro WhatsApp au format international (ex. +22670000000).',
                rules: ['max:30'],
                public: true,
            ),
            self::ADRESSE_SIEGE => $this->make(
                CompagnieSettingType::Text, CompagnieSettingGroup::General,
                'Adresse du siège', '',
                rules: ['max:500'],
                public: true,
            ),

            // ── Réservation ──────────────────────────────────────────────────
            self::RESERVATION_EN_LIGNE => $this->make(
                CompagnieSettingType::Boolean, CompagnieSettingGroup::Reservation,
                'Réservation en ligne', true,
                help: 'Désactivé, la compagnie n\'apparaît plus à la recherche dans l\'application mobile.',
                public: true,
            ),
            self::OUVERTURE_VENTE_JOURS => $this->make(
                CompagnieSettingType::Integer, CompagnieSettingGroup::Reservation,
                'Ouverture des ventes', 30,
                help: 'Nombre de jours à l\'avance pendant lesquels un voyage est réservable.',
                rules: ['min:1', 'max:365'], suffix: 'jours', public: true,
            ),
            self::CLOTURE_VENTE_MINUTES => $this->make(
                CompagnieSettingType::Integer, CompagnieSettingGroup::Reservation,
                'Clôture des ventes', 30,
                help: 'Les ventes s\'arrêtent ce nombre de minutes avant le départ.',
                rules: ['min:0', 'max:1440'], suffix: 'minutes', public: true,
            ),
            self::MAX_PLACES_PAR_RESERVATION => $this->make(
                CompagnieSettingType::Integer, CompagnieSettingGroup::Reservation,
                'Places maximum par réservation', 5,
                rules: ['min:1', 'max:50'], suffix: 'places', public: true,
            ),
            self::PIECE_IDENTITE_OBLIGATOIRE => $this->make(
                CompagnieSettingType::Boolean, CompagnieSettingGroup::Reservation,
                'Pièce d\'identité obligatoire', false,
                help: 'Exige un numéro de pièce d\'identité pour chaque passager.',
                public: true,
            ),
            self::SELECTION_SIEGE_ACTIVEE => $this->make(
                CompagnieSettingType::Boolean, CompagnieSettingGroup::Reservation,
                'Sélection du siège', true,
                help: 'Le voyageur choisit sa place lors de la réservation.',
                public: true,
            ),
            self::RESERVATION_POUR_AUTRUI => $this->make(
                CompagnieSettingType::Boolean, CompagnieSettingGroup::Reservation,
                'Réserver pour un tiers', true,
                help: 'Autorise l\'achat d\'un ticket au nom d\'une autre personne.',
                public: true,
            ),
            self::BAGAGE_GRATUIT_KG => $this->make(
                CompagnieSettingType::Integer, CompagnieSettingGroup::Reservation,
                'Franchise bagage', 20,
                rules: ['min:0', 'max:200'], suffix: 'kg', public: true,
            ),
            self::PRIX_KG_SUPPLEMENTAIRE => $this->make(
                CompagnieSettingType::Integer, CompagnieSettingGroup::Reservation,
                'Prix du kilo supplémentaire', 500,
                rules: ['min:0'], suffix: 'par kg', public: true,
            ),

            // ── Annulation & modification ────────────────────────────────────
            self::ANNULATION_AUTORISEE => $this->make(
                CompagnieSettingType::Boolean, CompagnieSettingGroup::Annulation,
                'Annulation autorisée', true, public: true,
            ),
            self::DELAI_ANNULATION => $this->make(
                CompagnieSettingType::Integer, CompagnieSettingGroup::Annulation,
                'Délai limite d\'annulation', 24,
                help: 'Nombre d\'heures avant le départ au-delà duquel l\'annulation est refusée.',
                rules: ['min:0', 'max:720'], suffix: 'heures', public: true,
            ),
            self::PENALITE_ANNULATION => $this->make(
                CompagnieSettingType::Integer, CompagnieSettingGroup::Annulation,
                'Pénalité d\'annulation', 10,
                help: 'Pourcentage retenu sur le montant remboursé.',
                rules: ['min:0', 'max:100'], suffix: '%', public: true,
            ),
            self::REMBOURSEMENT_AUTOMATIQUE => $this->make(
                CompagnieSettingType::Boolean, CompagnieSettingGroup::Annulation,
                'Remboursement automatique', false,
                help: 'Sinon, chaque remboursement doit être validé manuellement par la compagnie.',
            ),
            self::MODIFICATION_AUTORISEE => $this->make(
                CompagnieSettingType::Boolean, CompagnieSettingGroup::Annulation,
                'Modification de date autorisée', true, public: true,
            ),
            self::DELAI_MODIFICATION => $this->make(
                CompagnieSettingType::Integer, CompagnieSettingGroup::Annulation,
                'Délai limite de modification', 12,
                rules: ['min:0', 'max:720'], suffix: 'heures', public: true,
            ),
            self::FRAIS_MODIFICATION => $this->make(
                CompagnieSettingType::Integer, CompagnieSettingGroup::Annulation,
                'Frais de modification', 0,
                help: 'Montant fixe facturé pour un changement de date.',
                rules: ['min:0'], public: true,
            ),

            // ── Paiement ─────────────────────────────────────────────────────
            self::PAIEMENT_EN_LIGNE => $this->make(
                CompagnieSettingType::Boolean, CompagnieSettingGroup::Paiement,
                'Paiement en ligne', true,
                help: 'Autorise le règlement depuis l\'application mobile.',
                public: true,
            ),
            self::PAIEMENT_ESPECE_GUICHET => $this->make(
                CompagnieSettingType::Boolean, CompagnieSettingGroup::Paiement,
                'Paiement en espèces au guichet', true,
            ),
            self::MOYENS_PAIEMENT_ACTIFS => $this->make(
                CompagnieSettingType::MultiSelect, CompagnieSettingGroup::Paiement,
                'Moyens de paiement acceptés',
                [MoyenPayment::ORANGE_MONEY->value, MoyenPayment::MOOV_MONEY->value, MoyenPayment::ESPECE->value],
                help: 'Seuls les moyens cochés sont proposés au voyageur.',
                options: array_combine(MoyenPayment::values(), MoyenPayment::values()),
                public: true,
            ),
            self::EXPIRATION_PAIEMENT_MINUTES => $this->make(
                CompagnieSettingType::Integer, CompagnieSettingGroup::Paiement,
                'Expiration d\'un paiement en attente', 15,
                help: 'La réservation est libérée si le paiement n\'aboutit pas dans ce délai.',
                rules: ['min:1', 'max:1440'], suffix: 'minutes', public: true,
            ),
            self::FRAIS_SERVICE => $this->make(
                CompagnieSettingType::Integer, CompagnieSettingGroup::Paiement,
                'Frais de service', 0,
                rules: ['min:0'], public: true,
            ),
            self::FRAIS_SERVICE_TYPE => $this->make(
                CompagnieSettingType::Select, CompagnieSettingGroup::Paiement,
                'Mode de calcul des frais', 'fixe',
                options: ['fixe' => 'Montant fixe', 'pourcentage' => 'Pourcentage du billet'],
                public: true,
            ),

            // ── Tickets ──────────────────────────────────────────────────────
            self::QR_CODE_OBLIGATOIRE => $this->make(
                CompagnieSettingType::Boolean, CompagnieSettingGroup::Ticket,
                'QR code obligatoire', true,
                help: 'Le ticket doit être scanné pour être validé à l\'embarquement.',
            ),
            self::PREFIXE_NUMERO_TICKET => $this->make(
                CompagnieSettingType::String, CompagnieSettingGroup::Ticket,
                'Préfixe des numéros de ticket', 'TKT',
                help: 'Préfixe apposé devant le numéro séquentiel du ticket.',
                rules: ['max:10', 'regex:/^[A-Za-z0-9\-]*$/'],
            ),
            self::VALIDATION_AGENT_OBLIGATOIRE => $this->make(
                CompagnieSettingType::Boolean, CompagnieSettingGroup::Ticket,
                'Validation par un agent obligatoire', true,
            ),
            self::IMPRESSION_AUTO_APRES_VENTE => $this->make(
                CompagnieSettingType::Boolean, CompagnieSettingGroup::Ticket,
                'Impression automatique après vente', true,
                help: 'Ouvre le PDF du ticket dès la fin d\'une vente au guichet.',
            ),
            self::MENTIONS_LEGALES_TICKET => $this->make(
                CompagnieSettingType::Text, CompagnieSettingGroup::Ticket,
                'Mentions légales du ticket', '',
                help: 'Texte imprimé en pied de ticket (conditions de transport, responsabilité…).',
                rules: ['max:2000'],
            ),

            // ── Notifications ────────────────────────────────────────────────
            self::NOTIF_SMS_ACTIVE => $this->make(
                CompagnieSettingType::Boolean, CompagnieSettingGroup::Notification,
                'Notifications SMS', true,
            ),
            self::NOTIF_EMAIL_ACTIVE => $this->make(
                CompagnieSettingType::Boolean, CompagnieSettingGroup::Notification,
                'Notifications email', true,
            ),
            self::NOTIF_PUSH_ACTIVE => $this->make(
                CompagnieSettingType::Boolean, CompagnieSettingGroup::Notification,
                'Notifications push', true,
            ),
            self::NOTIF_WHATSAPP_ACTIVE => $this->make(
                CompagnieSettingType::Boolean, CompagnieSettingGroup::Notification,
                'Notifications WhatsApp', false,
            ),
            self::RAPPEL_DEPART_HEURES => $this->make(
                CompagnieSettingType::Integer, CompagnieSettingGroup::Notification,
                'Rappel avant le départ', 2,
                help: 'Envoi du rappel ce nombre d\'heures avant l\'heure de départ.',
                rules: ['min:0', 'max:72'], suffix: 'heures',
            ),
            self::SIGNATURE_SMS => $this->make(
                CompagnieSettingType::String, CompagnieSettingGroup::Notification,
                'Signature des SMS', '',
                help: 'Texte ajouté en fin de chaque SMS envoyé aux voyageurs.',
                rules: ['max:50'],
            ),

            // ── Fidélité & promotions ────────────────────────────────────────
            self::FIDELITE_ACTIVE => $this->make(
                CompagnieSettingType::Boolean, CompagnieSettingGroup::Fidelite,
                'Programme de fidélité', true, public: true,
            ),
            self::POINTS_PAR_MONTANT => $this->make(
                CompagnieSettingType::Integer, CompagnieSettingGroup::Fidelite,
                'Montant pour 1 point', 100,
                help: 'Montant dépensé donnant droit à un point de fidélité.',
                rules: ['min:1'], public: true,
            ),
            self::CODES_PROMO_ACTIFS => $this->make(
                CompagnieSettingType::Boolean, CompagnieSettingGroup::Fidelite,
                'Codes promotionnels', true, public: true,
            ),

            // ── Apparence ────────────────────────────────────────────────────
            self::COULEUR_PRIMAIRE => $this->make(
                CompagnieSettingType::Color, CompagnieSettingGroup::Apparence,
                'Couleur principale', '#2196F3',
                help: 'Utilisée dans l\'application mobile et sur les tickets.',
                public: true,
            ),
            self::COULEUR_SECONDAIRE => $this->make(
                CompagnieSettingType::Color, CompagnieSettingGroup::Apparence,
                'Couleur secondaire', '#1E293B', public: true,
            ),
            self::AFFICHER_LOGO_TICKET => $this->make(
                CompagnieSettingType::Boolean, CompagnieSettingGroup::Apparence,
                'Afficher le logo sur les tickets', true,
            ),

            // ── Avancé ───────────────────────────────────────────────────────
            self::COMMISSION_PLATEFORME => $this->make(
                CompagnieSettingType::Float, CompagnieSettingGroup::Avance,
                'Commission plateforme', 5.0,
                help: 'Pourcentage prélevé par LIPTRA sur chaque vente en ligne.',
                rules: ['min:0', 'max:100'], suffix: '%',
            ),
            self::PLAFOND_VENTE_JOURNALIER => $this->make(
                CompagnieSettingType::Integer, CompagnieSettingGroup::Avance,
                'Plafond de vente journalier', 0,
                help: '0 signifie aucun plafond.',
                rules: ['min:0'],
            ),
            self::MODE_MAINTENANCE => $this->make(
                CompagnieSettingType::Boolean, CompagnieSettingGroup::Avance,
                'Mode maintenance', false,
                help: 'Suspend toutes les ventes de la compagnie, guichet compris.',
                public: true,
            ),
            self::API_AGENT_ACTIVE => $this->make(
                CompagnieSettingType::Boolean, CompagnieSettingGroup::Avance,
                'Application agent activée', true,
                help: 'Autorise la connexion des agents depuis l\'application de contrôle.',
            ),
            self::DELAI_PAUSE_NON_CONSOMME => $this->make(
                CompagnieSettingType::Integer, CompagnieSettingGroup::Avance,
                'Mise en pause des billets non scannés', 3,
                help: 'Heures après le départ au bout desquelles un billet payé mais jamais '
                    .'scanné bascule en « Pause », d\'où il peut être reporté. Le battement '
                    .'laisse à l\'agent le temps de valider les embarquements tardifs.',
                rules: ['min:0', 'max:720'], suffix: 'heures',
            ),
        };
    }

    /**
     * @param  array<string, string>  $options
     * @param  array<int, string>     $rules
     */
    private function make(
        CompagnieSettingType $type,
        CompagnieSettingGroup $group,
        string $label,
        mixed $default,
        string $help = '',
        array $options = [],
        array $rules = [],
        string $suffix = '',
        bool $public = false,
        bool $adminOnly = false,
    ): SettingDefinition {
        return new SettingDefinition(
            key: $this, type: $type, group: $group, label: $label, default: $default,
            help: $help, options: $options, rules: $rules, suffix: $suffix,
            public: $public, adminOnly: $adminOnly,
        );
    }

    public function type(): CompagnieSettingType
    {
        return $this->definition()->type;
    }

    public function group(): CompagnieSettingGroup
    {
        return $this->definition()->group;
    }

    public function label(): string
    {
        return $this->definition()->label;
    }

    public function default(): mixed
    {
        return $this->definition()->default;
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Cases appartenant à un groupe donné.
     *
     * @return array<int, self>
     */
    public static function inGroup(CompagnieSettingGroup $group): array
    {
        return array_values(array_filter(self::cases(), fn (self $key) => $key->group() === $group));
    }
}
