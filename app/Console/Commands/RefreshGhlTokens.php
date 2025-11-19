<?php

namespace App\Console\Commands;

use App\User;
use App\Http\Controllers\GhlAuthController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefreshGhlTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ghl:refresh-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rafraîchit automatiquement les tokens GHL de tous les utilisateurs';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Démarrage du rafraîchissement des tokens GHL...');
        
        // Récupérer tous les utilisateurs avec un refresh_token
        $users = User::whereNotNull('ghl_refresh_token')->get();
        
        if ($users->isEmpty()) {
            $this->info('Aucun utilisateur avec un refresh_token à rafraîchir.');
            return 0;
        }

        $successCount = 0;
        $failureCount = 0;

        foreach ($users as $user) {
            $this->line("Rafraîchissement du token pour l'utilisateur ID: {$user->id} (Location: {$user->id_location})");
            
            try {
                $result = $this->refreshToken($user);
                
                if ($result) {
                    $successCount++;
                    $this->info("✓ Token rafraîchi avec succès pour l'utilisateur {$user->id}");
                } else {
                    $failureCount++;
                    $this->error("✗ Échec du rafraîchissement pour l'utilisateur {$user->id}");
                }
            } catch (\Exception $e) {
                $failureCount++;
                $this->error("✗ Erreur pour l'utilisateur {$user->id}: " . $e->getMessage());
                Log::error("Erreur lors du rafraîchissement du token GHL pour l'utilisateur {$user->id}", [
                    'error' => $e->getMessage(),
                    'user_id' => $user->id,
                    'location_id' => $user->id_location
                ]);
            }
        }

        $this->info("\n=== Résumé ===");
        $this->info("Total: " . $users->count());
        $this->info("Succès: {$successCount}");
        $this->info("Échecs: {$failureCount}");

        Log::info('Rafraîchissement automatique des tokens GHL terminé', [
            'total' => $users->count(),
            'success' => $successCount,
            'failure' => $failureCount
        ]);

        return 0;
    }

    /**
     * Rafraîchit le token d'un utilisateur
     *
     * @param User $user
     * @return bool
     */
    private function refreshToken(User $user)
    {
        return GhlAuthController::refreshUserToken($user);
    }
}
