<?php

namespace App\Services\Sync;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Curseur de pagination keyset pour le delta sync.
 *
 * On pagine sur le couple (updated_at, id) plutôt que sur un offset : entre deux
 * pages, des tickets sont validés et remontent dans l'ordre de tri, ce qui
 * décale un OFFSET et fait sauter des lignes. Un curseur keyset reprend
 * exactement où la page précédente s'est arrêtée.
 *
 * L'id départage les lignes partageant le même updated_at, sans quoi une page
 * pleine de timestamps identiques bouclerait indéfiniment.
 */
final readonly class SyncCursor
{
    public function __construct(
        public CarbonInterface $updatedAt,
        public int $id,
    ) {}

    /** Décode un curseur reçu du client, ou null s'il est absent ou illisible. */
    public static function decode(?string $raw): ?self
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $decoded = base64_decode(strtr($raw, '-_', '+/'), strict: true);
        if ($decoded === false || ! str_contains($decoded, '|')) {
            return null;
        }

        [$timestamp, $id] = explode('|', $decoded, 2);

        if (! ctype_digit($timestamp) || ! ctype_digit($id)) {
            return null;
        }

        return new self(Carbon::createFromTimestamp((int) $timestamp), (int) $id);
    }

    public function encode(): string
    {
        return rtrim(strtr(base64_encode($this->updatedAt->getTimestamp() . '|' . $this->id), '+/', '-_'), '=');
    }

    /**
     * Restreint la requête aux lignes situées après ce curseur.
     *
     * Le parenthésage du OR est indispensable : sans lui, la condition se
     * mélangerait aux autres filtres de la requête et ramènerait des lignes hors
     * périmètre.
     */
    public function applyTo(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('updated_at', '>', $this->updatedAt)
                ->orWhere(function (Builder $q) {
                    $q->where('updated_at', '=', $this->updatedAt)
                        ->where('id', '>', $this->id);
                });
        });
    }
}
