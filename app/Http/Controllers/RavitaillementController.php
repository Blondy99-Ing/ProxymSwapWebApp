<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Agence;
use App\Models\BatteriesValide;
use App\Models\BatteryAgence;
use App\Models\BMSData;

class RavitaillementController extends Controller
{
    /**
     * 🟡 Vue principale du ravitaillement.
     */
    public function index()
    {
        $stations = Agence::select('id', 'nom_agence', 'ville', 'quartier')->get();
        return view('ravitaillement', compact('stations'));
    }

    /**
     * 🟢 Retourne toutes les batteries (avec leur SOC et agence associée si applicable)
     */
    public function getAllBatteries()
    {
        try {
            $batteries = BatteriesValide::select('id', 'mac_id')
                ->orderBy('mac_id', 'asc')
                ->get()
                ->map(function ($b) {
                    $soc = $this->readSocByMac($b->mac_id);

                    // Vérifie si cette batterie est affectée à une agence
                    $agence = BatteryAgence::where('id_battery_valide', $b->id)
                        ->with('agence:id,nom_agence')
                        ->first();

                    return [
                        'id' => $b->id,
                        'mac_id' => $b->mac_id,
                        'soc' => $soc,
                        'agence_id' => $agence?->agence?->id ?? null,
                        'agence_nom' => $agence?->agence?->nom_agence ?? null,
                    ];
                });

            return response()->json($batteries);
        } catch (\Throwable $e) {
            Log::error("Erreur getAllBatteries : ".$e->getMessage());
            return response()->json(['message' => 'Erreur serveur : '.$e->getMessage()], 500);
        }
    }

    /**
     * 🔵 Batteries d’une agence spécifique
     */
    public function getBatteriesByAgence($agenceId)
    {
        try {
            $batteries = BatteryAgence::where('id_agence', $agenceId)
                ->with('batteryValide:id,mac_id')
                ->get()
                ->map(function ($ba) {
                    $mac = $ba->batteryValide->mac_id ?? null;
                    return [
                        'id' => $ba->batteryValide->id,
                        'mac_id' => $mac,
                        'soc' => $this->readSocByMac($mac),
                    ];
                });

            return response()->json($batteries);
        } catch (\Throwable $e) {
            Log::error("Erreur getBatteriesByAgence ($agenceId): ".$e->getMessage());
            return response()->json(['message' => 'Erreur serveur.'], 500);
        }
    }

    /**
     * POST /ravitaillement
     * body: { station_id: int, add: string[mac_id][], remove: string[mac_id][] }
     */
   
    /**
     * POST /ravitaillement
     * body: { station_id: int, add: string[mac_id][], remove: string[mac_id][] }
     */
    public function store(Request $request)
    {
        // 1) Validation basique
        $validated = $request->validate([
            'station_id' => 'required|integer|exists:agences,id',
            'add'        => 'array',
            'remove'     => 'array',
        ]);

        $stationId = (int) $validated['station_id'];
        $addMacs   = array_values($validated['add']    ?? []);
        $remMacs   = array_values($validated['remove'] ?? []);

        // 2) LOG d’entrée
        Log::info('RAVITAILLEMENT/STORE: payload reçu', [
            'station_id' => $stationId,
            'add_macs'   => $addMacs,
            'remove_macs'=> $remMacs,
        ]);

        // 3) Résoudre les MAC -> batteries (id, mac_id)
        $allMacs        = array_values(array_unique(array_merge($addMacs, $remMacs)));
        $batsByMac      = BatteriesValide::whereIn('mac_id', $allMacs)->get(['id', 'mac_id'])
                            ->keyBy('mac_id');

        $resolvedAdd    = [];
        $resolvedRemove = [];
        $missingAdd     = [];
        $missingRemove  = [];

        foreach ($addMacs as $mac) {
            $bat = $batsByMac->get($mac);
            $bat ? $resolvedAdd[] = ['id' => $bat->id, 'mac_id' => $bat->mac_id] : $missingAdd[] = $mac;
        }
        foreach ($remMacs as $mac) {
            $bat = $batsByMac->get($mac);
            $bat ? $resolvedRemove[] = ['id' => $bat->id, 'mac_id' => $bat->mac_id] : $missingRemove[] = $mac;
        }

        // 4) LOG résolution
        Log::info('RAVITAILLEMENT/STORE: résolution MAC → ID', [
            'resolved_add'     => $resolvedAdd,
            'resolved_remove'  => $resolvedRemove,
            'missing_add'      => $missingAdd,     // ces MAC n’existent pas dans batteries_valides
            'missing_remove'   => $missingRemove,  // idem
        ]);

        DB::beginTransaction();
        try {
            // 5) RETRAITS: supprimer de la station sélectionnée uniquement
            $removedCount = 0;
            foreach ($resolvedRemove as $r) {
                $deleted = BatteryAgence::where('id_agence', $stationId)
                    ->where('id_battery_valide', $r['id'])
                    ->delete();
                $removedCount += $deleted;

                Log::info('RAVITAILLEMENT/STORE: retrait batterie', [
                    'station_id' => $stationId,
                    'battery'    => $r,
                    'deleted'    => $deleted,
                ]);
            }

            // 6) AJOUTS: enlever l’affectation éventuelle (toute agence) puis rattacher à la station
            $detachedBeforeAdd = 0;
            $upsertsCount      = 0;

            foreach ($resolvedAdd as $r) {
                // Supprimer l’ancienne liaison si elle existe (n’importe quelle agence)
                $detached = BatteryAgence::where('id_battery_valide', $r['id'])->delete();
                $detachedBeforeAdd += $detached;

                Log::info('RAVITAILLEMENT/STORE: détachement éventuel avant ajout', [
                    'battery'  => $r,
                    'detached' => $detached,
                ]);

                // Rattacher proprement à la station cible
                BatteryAgence::updateOrCreate(
                    ['id_battery_valide' => $r['id']],
                    ['id_agence' => $stationId]
                );
                $upsertsCount++;

                Log::info('RAVITAILLEMENT/STORE: ajout batterie à la station', [
                    'station_id' => $stationId,
                    'battery'    => $r,
                ]);
            }

            DB::commit();

            // 7) LOG de synthèse + réponse
            $summary = [
                'station_id'           => $stationId,
                'received'             => ['add' => $addMacs, 'remove' => $remMacs],
                'resolved_add_count'   => count($resolvedAdd),
                'resolved_remove_count'=> count($resolvedRemove),
                'missing_add'          => $missingAdd,
                'missing_remove'       => $missingRemove,
                'removed_from_station' => $removedCount,
                'detached_before_add'  => $detachedBeforeAdd,
                'added_to_station'     => $upsertsCount,
            ];
            Log::info('RAVITAILLEMENT/STORE: résumé transaction', $summary);

            return response()->json([
                'message' => '✅ Ravitaillement effectué.',
                'summary' => $summary,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('RAVITAILLEMENT/STORE: erreur transaction', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => '❌ Erreur interne: '.$e->getMessage(),
            ], 500);
        }
    }


    /**
     * 🔋 Helper — Lecture du SOC en temps réel depuis BMSData
     */
    private function readSocByMac(?string $mac): int
    {
        if (!$mac) return 50;
        $bms = BMSData::where('mac_id', $mac)->latest('id')->first();
        if (!$bms || empty($bms->state)) return 50;

        try {
            $state = json_decode($bms->state, true);
            $soc = $state['SOC'] ?? $state['SoC'] ?? 50;
            return max(0, min(100, (int)$soc));
        } catch (\Throwable $e) {
            return 50;
        }
    }
}
