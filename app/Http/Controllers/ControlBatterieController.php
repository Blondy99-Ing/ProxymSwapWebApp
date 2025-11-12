<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BatteriesValide;
use App\Models\BMSData;
use App\Services\CommandBatteryService;
use Illuminate\Support\Facades\Log;

class ControlBatterieController extends Controller
{
    protected CommandBatteryService $commandBatteryService;

    public function __construct(CommandBatteryService $commandBatteryService)
    {
        $this->commandBatteryService = $commandBatteryService;
    }

    // =========================================================================
    // VUE ET DONNÉES INITIALES
    // =========================================================================

    /**
     * Affiche la vue de contrôle des batteries.
     * Passe la liste des MAC IDs valides au JavaScript pour le chargement dynamique.
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // 1. Récupération optimisée de la liste des MAC IDs
        $batteriesMacIds = BatteriesValide::pluck('mac_id')->toArray();

        // Renvoie la vue HTML
        return view('controle_batterie', ['batteriesMacIds' => $batteriesMacIds]);
    }

    // =========================================================================
    // API - LECTURE DES STATUTS
    // =========================================================================

    /**
     * Récupère le statut de TOUTES les batteries valides en batch (optimisation).
     * Utilisé pour le chargement initial de la grille.
     * Route: GET /batteries/api/status/all
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllBatteriesStatus()
    {
        $macIds = BatteriesValide::pluck('mac_id');

        if ($macIds->isEmpty()) {
            return response()->json(['message' => 'Aucune batterie valide trouvée.', 'data' => []], 200);
        }
        
        // 1. Trouver les IDs des dernières entrées BMS pour chaque mac_id
        $latestBmsIds = BMSData::selectRaw('MAX(id) as max_id')
              ->whereIn('mac_id', $macIds)
              ->groupBy('mac_id')
              ->pluck('max_id');

        // 2. Récupérer les dernières données BMS en une seule requête DB
        $latestBms = BMSData::select('mac_id', 'seting')
            ->whereIn('id', $latestBmsIds)
            ->get()
            ->keyBy('mac_id'); 

        $results = [];

        // 3. Traiter les données et préparer la réponse
        foreach ($macIds as $macId) {
            $bmsData = $latestBms->get($macId);
            $status = $this->processBmsData($bmsData, $macId);
            
            $results[] = $status ?? [
                'mac_id' => $macId,
                'soc' => 50, 
                'is_charging_on' => false,
                'is_discharging_on' => false,
                'message' => 'Données seting absentes ou invalides.',
            ];
        }

        return response()->json(['data' => $results]);
    }

    /**
     * Récupère le statut actuel (CHON/DHON et SOC) pour une SEULE batterie.
     * Utilisé pour la mise à jour après une commande.
     * Route: GET /batteries/api/status/{macId}
     * @param string $macId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBatteryStatus(string $macId)
    {
        // Récupérer la dernière donnée BMS
        $bmsData = BMSData::where('mac_id', $macId)->latest('id')->first(['seting']);
        $data = $this->processBmsData($bmsData, $macId);
        
        if (!$data) {
             return response()->json([
                'message' => 'Données BMS (seting) non trouvées.',
                'mac_id' => $macId,
                'soc' => 50, 
                'is_charging_on' => false,
                'is_discharging_on' => false,
            ], 404);
        }

        return response()->json($data);
    }
    
    // =========================================================================
    // FONCTION DE TRAITEMENT INTERNE
    // =========================================================================

    /**
     * Lit et parse les données nécessaires (SOC, CHON, DHON) depuis la colonne 'seting'.
     * @param \App\Models\BMSData|null $bms
     * @param string $macId
     * @return array|null ['mac_id' => string, 'soc' => int, 'is_charging_on' => bool, 'is_discharging_on' => bool]
     */
    private function processBmsData(?BMSData $bms, string $macId): ?array
    {
        if (!$bms || empty($bms->seting)) return null;

        try {
            $state = json_decode($bms->seting, true);

            if (!is_array($state)) {
                Log::error("Contenu seting non valide (non-array) pour MAC ID: {$macId}.");
                return null;
            }

            // Extraction sécurisée des données
            $soc = max(0, min(100, (int)($state['SOC'] ?? $state['SoC'] ?? 50)));
            $chon = (int)($state['CHON'] ?? 0);
            $dhon = (int)($state['DHON'] ?? 0);

            return [
                'mac_id' => $macId,
                'soc' => $soc,
                'is_charging_on' => (bool)$chon,
                'is_discharging_on' => (bool)$dhon,
            ];
        } catch (\Throwable $e) {
            Log::error("Erreur décodage/traitement BMS seting pour {$macId}: ".$e->getMessage(), ['content' => substr($bms->seting, 0, 100)]);
            return null;
        }
    }

    // =========================================================================
    // API - ENVOI DE COMMANDE
    // =========================================================================

    /**
     * Envoie une commande ON/OFF à un BMS spécifique.
     * Route: POST /batteries/api/command
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendBmsCommand(Request $request)
    {
        $validated = $request->validate([
            'mac_id' => 'required|string|exists:batteries_valides,mac_id',
            'action' => 'required|string|in:charge_on,charge_off,discharge_on,discharge_off'
        ]);

        $macId = $validated['mac_id'];
        $action = $validated['action'];

        Log::info("Tentative de commande BMS: {$action} pour MAC ID: {$macId}");

        $result = $this->commandBatteryService->sendCommand($macId, $action);

        if ($result['success']) {
            return response()->json(['success' => true, 'message' => $result['message']], 200);
        } else {
            $errorMsg = $result['error'] ?? "Échec de l'envoi de la commande {$action}.";
            Log::warning("Échec de la commande BMS pour {$macId}: {$errorMsg}");
            return response()->json(['success' => false, 'message' => $errorMsg], 500);
        }
    }
}