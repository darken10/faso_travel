<?php

namespace App\Contracts;

use App\Enums\OtpChannelType;
use App\Models\User;

/**
 * Contrat d'un canal d'envoi d'OTP.
 *
 * Chaque canal (mail, SMS, WhatsApp, Telegram…) implémente cette interface.
 * Le pattern Strategy permet d'ajouter / remplacer un canal sans toucher
 * au reste du code : il suffit d'écrire une nouvelle classe et de l'enregistrer
 * dans OtpChannelManager.
 */
interface OtpChannel
{
    /** Type du canal. */
    public function type(): OtpChannelType;

    /** Le canal peut-il être utilisé pour cet utilisateur (données requises présentes / config OK) ? */
    public function isAvailableFor(User $user): bool;

    /** Destination masquée pour affichage (ex. "j***@mail.com", "+226 ** ** 12"). */
    public function maskedDestination(User $user): ?string;

    /** Envoie l'OTP à l'utilisateur via ce canal. */
    public function send(User $user, string $otp): void;
}
