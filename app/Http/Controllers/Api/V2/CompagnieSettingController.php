<?php

namespace App\Http\Controllers\Api\V2;

use App\DTOs\Compagnie\SettingDefinition;
use App\Enums\CompagnieSettingKey;
use App\Http\Controllers\Controller;
use App\Models\Compagnie\Compagnie;
use App\Services\Compagnie\CompagnieSettingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Exposition du paramétrage compagnie aux applications mobiles.
 *
 * L'application cliente lit les paramètres publics d'une compagnie ;
 * l'application agent lit et écrit ceux de sa propre compagnie.
 */
class CompagnieSettingController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly CompagnieSettingService $settings) {}

    /** Paramètres publics d'une compagnie — consommés par l'application voyageur. */
    public function publicSettings(Compagnie $compagnie): JsonResponse
    {
        return $this->successResponse([
            'compagnie_id' => $compagnie->id,
            'settings'     => $this->settings->publicSettings($compagnie),
        ]);
    }

    /** Paramètres complets de la compagnie de l'utilisateur authentifié. */
    public function index(Request $request): JsonResponse
    {
        $compagnie = $this->currentCompagnie($request);

        Gate::authorize('compagnie-settings.view', $compagnie);

        $canManageAdvanced = Gate::allows('compagnie-settings.updateAdvanced');

        return $this->successResponse([
            'compagnie_id'    => $compagnie->id,
            'settings'        => $this->visibleSettings($compagnie, $canManageAdvanced),
            'customized_keys' => $this->settings->customizedKeys($compagnie),
            'catalogue'       => $this->catalogue($canManageAdvanced),
            'can_update'      => Gate::allows('compagnie-settings.update', $compagnie),
        ]);
    }

    /**
     * Met à jour les paramètres de la compagnie de l'utilisateur authentifié.
     *
     * Attend `{"settings": {"cle": valeur, ...}}`.
     */
    public function update(Request $request): JsonResponse
    {
        $compagnie = $this->currentCompagnie($request);

        Gate::authorize('compagnie-settings.update', $compagnie);

        $payload = $request->validate([
            'settings' => 'required|array|min:1',
        ])['settings'];

        try {
            $this->settings->sync($compagnie, $payload, Gate::allows('compagnie-settings.updateAdvanced'));
        } catch (ValidationException $e) {
            return $this->errorResponse('Certains paramètres sont invalides.', 422, $e->errors());
        }

        return $this->successResponse(
            ['settings' => $this->visibleSettings($compagnie, Gate::allows('compagnie-settings.updateAdvanced'))],
            'Paramètres enregistrés.',
        );
    }

    /**
     * Valeurs visibles par l'appelant : les paramètres avancés sont masqués
     * aux comptes qui ne sont pas administrateurs de la plateforme.
     *
     * @return array<string, mixed>
     */
    private function visibleSettings(Compagnie $compagnie, bool $canManageAdvanced): array
    {
        $all = $this->settings->all($compagnie);

        if ($canManageAdvanced) {
            return $all;
        }

        return collect($all)
            ->reject(fn ($value, $key) => CompagnieSettingKey::from($key)->definition()->isAdminOnly())
            ->all();
    }

    /**
     * Description du catalogue, pour un rendu dynamique du formulaire côté mobile.
     *
     * @return array<int, array<string, mixed>>
     */
    private function catalogue(bool $includeAdminOnly): array
    {
        return collect($this->settings->catalogue($includeAdminOnly))
            ->map(fn (array $entry) => [
                'group'       => $entry['group']->value,
                'label'       => $entry['group']->label(),
                'description' => $entry['group']->description(),
                'settings'    => array_map(
                    fn (SettingDefinition $definition) => $definition->toArray(),
                    $entry['definitions'],
                ),
            ])
            ->values()
            ->all();
    }

    private function currentCompagnie(Request $request): Compagnie
    {
        $compagnieId = $request->user()?->compagnie_id;

        abort_if($compagnieId === null, 403, 'Compte non associé à une compagnie.');

        return Compagnie::findOrFail($compagnieId);
    }
}
