<?php

namespace Tests\Unit\Rbac;

use App\Models\Compagnie\Care;
use App\Models\Compagnie\Compagnie;
use App\Models\Finance\Caisse;
use App\Models\User;
use App\Rbac\PorteeVerifier;
use Tests\TestCase;

/**
 * Le vérificateur de portée ne touche pas la base : il travaille sur des instances non
 * persistées, ce qui garde ces tests rapides et indépendants des migrations.
 */
class PorteeVerifierTest extends TestCase
{
    private PorteeVerifier $verifieur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->verifieur = new PorteeVerifier;
    }

    public function test_la_portee_all_autorise_tout(): void
    {
        $this->assertTrue($this->verifieur->autorise($this->agent(1), 'all', $this->vehicule(99)));
    }

    public function test_sans_sujet_le_controle_porte_sur_un_ecran_et_passe(): void
    {
        foreach (['all', 'compagnie', 'gare', 'own'] as $portee) {
            $this->assertTrue(
                $this->verifieur->autorise($this->agent(1), $portee),
                "La portée {$portee} devrait laisser passer un contrôle d'écran."
            );
        }
    }

    public function test_portee_compagnie_accepte_un_sujet_de_sa_compagnie(): void
    {
        $this->assertTrue(
            $this->verifieur->autorise($this->agent(7), 'compagnie', $this->vehicule(7))
        );
    }

    public function test_portee_compagnie_refuse_un_sujet_d_une_autre_compagnie(): void
    {
        $this->assertFalse(
            $this->verifieur->autorise($this->agent(7), 'compagnie', $this->vehicule(8))
        );
    }

    public function test_portee_compagnie_refuse_un_compte_sans_compagnie(): void
    {
        $this->assertFalse(
            $this->verifieur->autorise($this->agent(null), 'compagnie', $this->vehicule(7))
        );
    }

    public function test_portee_compagnie_accepte_le_modele_compagnie_lui_meme(): void
    {
        $compagnie = new Compagnie;
        $compagnie->id = 7;

        $this->assertTrue($this->verifieur->autorise($this->agent(7), 'compagnie', $compagnie));
        $this->assertFalse($this->verifieur->autorise($this->agent(8), 'compagnie', $compagnie));
    }

    public function test_portee_own_accepte_son_propre_enregistrement(): void
    {
        $caisse = new Caisse(['user_id' => 42, 'compagnie_id' => 7]);

        $this->assertTrue($this->verifieur->autorise($this->agent(7, 42), 'own', $caisse));
    }

    public function test_portee_own_refuse_l_enregistrement_d_un_autre(): void
    {
        $caisse = new Caisse(['user_id' => 43, 'compagnie_id' => 7]);

        $this->assertFalse($this->verifieur->autorise($this->agent(7, 42), 'own', $caisse));
    }

    public function test_portee_own_sur_son_propre_compte(): void
    {
        $autre = $this->agent(7, 43);

        $this->assertTrue($this->verifieur->autorise($this->agent(7, 42), 'own', $this->agent(7, 42)));
        $this->assertFalse($this->verifieur->autorise($this->agent(7, 42), 'own', $autre));
    }

    public function test_portee_own_refuse_un_sujet_sans_proprietaire(): void
    {
        // Propriété indéterminable : le doute ne doit jamais se résoudre en autorisation.
        $this->assertFalse(
            $this->verifieur->autorise($this->agent(7, 42), 'own', $this->vehicule(7))
        );
    }

    public function test_portee_gare_se_comporte_comme_la_portee_compagnie(): void
    {
        // Comportement voulu tant que la table gare_user n'existe pas : refuser
        // retirerait d'un coup l'accès des guichetiers et des agents.
        $this->assertTrue($this->verifieur->autorise($this->agent(7), 'gare', $this->vehicule(7)));
        $this->assertFalse($this->verifieur->autorise($this->agent(7), 'gare', $this->vehicule(8)));
    }

    public function test_une_portee_inconnue_est_refusee(): void
    {
        $this->assertFalse(
            $this->verifieur->autorise($this->agent(7), 'region', $this->vehicule(7))
        );
    }

    public function test_la_portee_la_plus_large_est_retenue(): void
    {
        $this->assertSame('all', PorteeVerifier::plusLarge('own', 'all'));
        $this->assertSame('compagnie', PorteeVerifier::plusLarge('compagnie', 'gare'));
        $this->assertSame('gare', PorteeVerifier::plusLarge('own', 'gare'));
        $this->assertSame('own', PorteeVerifier::plusLarge('own', 'own'));
    }

    private function agent(?int $compagnieId, int $id = 1): User
    {
        $user = new User(['compagnie_id' => $compagnieId]);
        $user->id = $id;

        return $user;
    }

    private function vehicule(int $compagnieId): Care
    {
        return new Care(['compagnie_id' => $compagnieId]);
    }
}
