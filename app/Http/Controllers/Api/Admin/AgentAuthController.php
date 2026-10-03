<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auth\PersonalAccessToken;
use App\Models\User;
use App\Rbac\ControleAcces;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AgentAuthController extends Controller
{
    /**
     * Un agent peut rester plusieurs jours hors réseau : son jeton d'accès doit
     * survivre à l'absence de connexion, sans pour autant rester valable
     * indéfiniment si le téléphone est perdu.
     */
    private const ACCESS_TTL_DAYS = 1;

    private const REFRESH_TTL_DAYS = 30;

    /**
     * Login d'un agent par email OU numéro de téléphone.
     *
     * Payload : { "credential": "...", "password": "...", "device_id": "..." }
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'credential' => 'required|string',
            'password' => 'required|string',
            'device_id' => 'nullable|string|max:64',
        ]);

        $credential = trim($request->input('credential'));
        $field = filter_var($credential, FILTER_VALIDATE_EMAIL) ? 'email' : 'numero';

        $user = User::where($field, $credential)
            ->whereNotNull('compagnie_id')
            ->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants incorrects. Veuillez contacter votre administrateur.',
            ], 401);
        }

        if (! $this->peutUtiliserApplicationAgent($user)) {
            return response()->json([
                'success' => false,
                'message' => "Ce compte n'est pas autorisé à utiliser l'application agent.",
            ], 403);
        }

        $tokens = $this->issueTokens($user, $this->deviceKey($request->input('device_id')));

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->numero,
                'avatar' => $user->photo ?? null,
                'compagnie' => $user->compagnie ? [
                    'id' => $user->compagnie->id,
                    'name' => $user->compagnie->name,
                    'logo' => $user->compagnie->logo ?? null,
                ] : null,
            ],
            ...$tokens,
        ]);
    }

    /**
     * Renouvelle la paire de jetons.
     *
     * Sans cela, un agent revenant d'une zone blanche après 24 h recevait 401
     * sur chaque tentative de synchronisation, et ne pouvait rien faire d'autre
     * que ressaisir son mot de passe — ce qui aurait obligé l'application à le
     * conserver en clair sur le téléphone.
     */
    public function refresh(Request $request): JsonResponse
    {
        $request->validate(['refresh_token' => 'required|string']);

        $token = PersonalAccessToken::findToken($request->input('refresh_token'));

        if (! $token || ! str_starts_with((string) $token->name, self::refreshPrefix()) || $token->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'Session expirée. Veuillez vous reconnecter.',
            ], 401);
        }

        /** @var User $user */
        $user = $token->tokenable;

        if ($user === null || $user->compagnie_id === null) {
            return response()->json([
                'success' => false,
                'message' => 'Compte non associé à une compagnie.',
            ], 403);
        }

        // Le renouvellement revérifie l'habilitation : un agent muté au guichet garde
        // sinon un accès à l'application de quai pendant trente jours.
        if (! $this->peutUtiliserApplicationAgent($user)) {
            return response()->json([
                'success' => false,
                'message' => "Ce compte n'est plus autorisé à utiliser l'application agent.",
            ], 403);
        }

        // Le device est encodé dans le nom du jeton présenté : le renouvellement
        // reste donc cantonné à l'appareil d'origine.
        $device = substr((string) $token->name, strlen(self::refreshPrefix()));

        return response()->json([
            'success' => true,
            ...$this->issueTokens($user, $device),
        ]);
    }

    /**
     * Émet une paire access/refresh pour un appareil donné.
     *
     * Seuls les jetons de CE téléphone sont révoqués. L'ancien code faisait
     * $user->tokens()->delete() : se connecter sur un second appareil coupait le
     * premier, dont la file d'opérations hors ligne devenait insynchronisable.
     *
     * @return array{token:string,refresh_token:string,expires_at:string,refresh_expires_at:string}
     */
    private function issueTokens(User $user, string $device): array
    {
        $accessName = self::accessPrefix().$device;
        $refreshName = self::refreshPrefix().$device;

        $user->tokens()->whereIn('name', [$accessName, $refreshName])->delete();

        $accessExpiresAt = now()->addDays(self::ACCESS_TTL_DAYS);
        $refreshExpiresAt = now()->addDays(self::REFRESH_TTL_DAYS);

        return [
            'token' => $user->createToken($accessName, $this->abilitesAgent($user), $accessExpiresAt)->plainTextToken,
            'refresh_token' => $user->createToken($refreshName, ['refresh'], $refreshExpiresAt)->plainTextToken,
            'expires_at' => $accessExpiresAt->toIso8601String(),
            'refresh_expires_at' => $refreshExpiresAt->toIso8601String(),
        ];
    }

    /**
     * Le compte est-il habilité à l'application de quai ?
     *
     * Le contrôle manquait : tout compte rattaché à une compagnie obtenait un jeton, donc
     * un comptable ou un chargé de communication pouvait valider des tickets à
     * l'embarquement.
     *
     * Le mode observation s'applique ici aussi, pour ne pas couper les agents en
     * production avant que leurs rôles ne soient en place.
     */
    private function peutUtiliserApplicationAgent(User $user): bool
    {
        return app(ControleAcces::class)->autorise(
            $user,
            'embarquement.app.login',
            contexte: ['origine' => 'admin.auth'],
        );
    }

    /**
     * Abilités portées par le jeton d'accès.
     *
     * Un jeton `['*']` autorise tout ce que l'API expose : si le jeton d'un téléphone
     * perdu est rejoué, il vaut mieux qu'il ne porte que les opérations de quai. On liste
     * donc les permissions d'embarquement réellement détenues.
     *
     * @return list<string>
     */
    private function abilitesAgent(User $user): array
    {
        $abilites = [];

        foreach (array_keys($user->permissionsEffectives()) as $permission) {
            if (str_starts_with($permission, 'embarquement.')) {
                $abilites[] = $permission;
            }
        }

        // Un compte encore sans rôle d'embarquement (mode observation) doit continuer à
        // fonctionner : sans abilité, chaque appel protégé serait refusé par Sanctum.
        return $abilites === [] ? ['*'] : $abilites;
    }

    /**
     * Identifiant d'appareil normalisé.
     *
     * Restreint aux caractères sûrs : il est concaténé dans le nom du jeton, qui
     * sert ensuite de critère de révocation.
     */
    private function deviceKey(?string $deviceId): string
    {
        $clean = preg_replace('/[^A-Za-z0-9._-]/', '', (string) $deviceId);

        return $clean !== '' ? substr($clean, 0, 64) : 'default';
    }

    private static function accessPrefix(): string
    {
        return 'agent_access:';
    }

    private static function refreshPrefix(): string
    {
        return 'agent_refresh:';
    }
}
