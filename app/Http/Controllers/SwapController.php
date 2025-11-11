<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Agence;
use App\Models\UsersAgence;
use App\Models\BatteriesValide;
use App\Models\BatteryAgence;
use App\Models\BatteryMotoUserAssociation;
use App\Models\BMSData;
use App\Models\Swap;
use App\Models\Employe;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\CommandBatteryService;


class SwapController extends Controller
{
     protected $commandBatteryService;

    public function __construct(CommandBatteryService $commandBatteryService)
    {
        $this->commandBatteryService = $commandBatteryService;
    }

 


    public function index()
    {
        $stations = Agence::select('id', 'nom_agence', 'ville', 'quartier')->get();
        return view('swap', compact('stations'));
    }

    public function getAgentsByStation($stationId)
    {
        $agents = UsersAgence::where('id_agence', $stationId)
            ->select('id', 'nom', 'prenom')
            ->get();

        return response()->json($agents);
    }

    public function getBatteriesByStation($stationId)
    {
        $batteries = BatteryAgence::where('id_agence', $stationId)
            ->with('batteryValide:id,mac_id,batterie_unique_id')
            ->get()
            ->map(function ($batteryAgence) {
                $battery = $batteryAgence->batteryValide;
                $soc = $this->readSocByMac($battery->mac_id);

                return [
                    'id' => $battery->id,
                    'mac_id' => $battery->mac_id,
                    'soc' => $soc ?? 50, // Fallback si pas de SOC
                ];
            });

        return response()->json($batteries);
    }

    public function getChauffeurByBattery($partialMacId)
    {
        $battery = BatteriesValide::where('mac_id', 'like', '%' . $partialMacId)
            ->select('id', 'mac_id')
            ->first();

        if (!$battery) {
            return response()->json(['message' => 'Aucune batterie trouvée avec ces chiffres.'], 404);
        }

        $batteryAssociation = BatteryMotoUserAssociation::where('battery_id', $battery->id)
            ->latest('date_association')
            ->first();

        if (!$batteryAssociation) {
            return response()->json(['message' => 'Aucune association trouvée pour cette batterie.'], 404);
        }

        $userMotoAssociation = \App\Models\AssociationUserMoto::where('id', $batteryAssociation->association_user_moto_id)
            ->with(['validatedUser', 'motosValide'])
            ->first();

        if (!$userMotoAssociation) {
            return response()->json(['message' => 'Aucune correspondance trouvée pour cette batterie.'], 404);
        }

        $chauffeur = $userMotoAssociation->validatedUser;
        $moto = $userMotoAssociation->motosValide;
        $socOut = $this->readSocByMac($battery->mac_id) ?? 50;

        return response()->json([
            'mac_id_complet' => $battery->mac_id,
            'id_battery_valide' => $battery->id,
            'battery_moto_user_association_id' => $batteryAssociation->id,
            'chauffeur_nom' => $chauffeur->nom ?? '—',
            'chauffeur_prenom' => $chauffeur->prenom ?? '—',
            'chauffeur_phone' => $chauffeur->phone ?? '—',
            'moto_vin' => $moto->vin ?? '—',
            'soc' => $socOut,
        ]);
    }


public function getSwapPrice(Request $request)
{
    $data = $request->validate([
        'battery_in_id' => 'required|integer|exists:batteries_valides,id',
        'battery_out_id' => 'required|integer|exists:batteries_valides,id',
    ]);

    $batteryIn = BatteriesValide::findOrFail($data['battery_in_id']);
    $batteryOut = BatteriesValide::findOrFail($data['battery_out_id']);

    // Lecture des valeurs SOC & SYLA
    $socIn = $this->readSocByBatteryId($batteryIn->id) ?? 50;
    $socOut = $this->readSocByBatteryId($batteryOut->id) ?? 50;
    $sylaIn = $this->readSYLAByBatteryId($batteryIn->id) ?? 100;
    $sylaOut = $this->readSYLAByBatteryId($batteryOut->id) ?? 100;

    // 🔹 Log pour vérifier SOC et SYLA
    Log::info("Calcul prix swap : SOC In={$socIn}, SOC Out={$socOut}, SYLA In={$sylaIn}, SYLA Out={$sylaOut}");

    // Calcul du prix façon Node.js
    $deltaSOC = $socOut - $socIn;
    $raw = ($deltaSOC * 1500) / 90;
    $basePrice = max(0, min($raw, 1500));
    $adjustedPrice = $basePrice * ($sylaOut / max($sylaOut, 45));
    $swapPrice = ceil($adjustedPrice / 100) * 100;

    return response()->json([
        'battery_in_soc'  => (int) $socIn,
        'battery_out_soc' => (int) $socOut,
        'battery_in_syla' => (float) $sylaIn,
        'battery_out_syla'=> (float) $sylaOut,
        'delta_soc'       => $deltaSOC,
        'prix'            => (int) $swapPrice,
    ]);
}





  
     /**
     * Valider un swap de batterie
     */
public function faireSwap(Request $request)
{
    Log::info('--- Données reçues pour le swap ---', $request->all());

    // 1) Validation
    $validated = $request->validate([
        'battery_in_id'  => 'required|integer|exists:batteries_valides,id',  // déposée par le chauffeur (entre en station)
        'battery_out_id' => 'required|integer|exists:batteries_valides,id', // remise au chauffeur (sort de station)
        'agent_user_id'  => 'required|integer',
        'id_agence'      => 'required|integer',
        'nom'            => 'required|string',
        'prenom'         => 'required|string',
        'phone'          => 'required|string',
        'employe_id'     => 'required|integer',
        'swap_price'     => 'nullable|integer',
    ]);

    $batteryIn  = BatteriesValide::findOrFail($validated['battery_in_id']);
    $batteryOut = BatteriesValide::findOrFail($validated['battery_out_id']);

    $prix = $validated['swap_price'];

    $inMacId  = $batteryIn->mac_id;
    $outMacId = $batteryOut->mac_id;

    // 🔹 Lecture SOC & SYLA avec les méthodes privées
    $socIn   = $this->readSocByBatteryId($batteryIn->id);
    $socOut  = $this->readSocByBatteryId($batteryOut->id);
    $sylaIn  = $this->readSYLAByBatteryId($batteryIn->id);
    $sylaOut = $this->readSYLAByBatteryId($batteryOut->id);

    Log::info("Swap SOC : In={$socIn}, Out={$socOut}");
    Log::info("Swap SYLA : In={$sylaIn}, Out={$sylaOut}");

    DB::beginTransaction();
    try {
        // 2) Retirer battery_in de la station puis rattacher à la station courante
        DB::table('battery_agences')
            ->where('id_battery_valide', $batteryIn->id)
            ->delete();

        BatteryAgence::updateOrCreate(
            ['id_battery_valide' => $batteryIn->id],
            ['id_agence' => $validated['id_agence']]
        );

        // 3) Supprimer toute association en cours pour battery_out
        BatteryMotoUserAssociation::where('battery_id', $batteryOut->id)
            ->update(['battery_id' => null]);

        // 4) Récupérer l’association du chauffeur (celle qui possédait battery_in)
        $ancienneAssoc = BatteryMotoUserAssociation::where('battery_id', $batteryIn->id)
            ->orderByDesc('date_association')
            ->lockForUpdate()
            ->first();

        if (!$ancienneAssoc) {
            throw new \RuntimeException("Aucune association active trouvée pour la batterie déposée (ID={$batteryIn->id}).");
        }

        // 5) Associer le chauffeur à la nouvelle batterie (swap)
        $ancienneAssoc->update(['battery_id' => $batteryOut->id]);

        // 6) Supprimer battery_out de la station
        DB::table('battery_agences')
            ->where('id_battery_valide', $batteryOut->id)
            ->delete();

        // 7) Enregistrement du swap
        $swap = Swap::create([
            'battery_moto_user_association_id' => $ancienneAssoc->id,
            'battery_in_id'     => $batteryIn->id,
            'battery_out_id'    => $batteryOut->id,
            'agent_user_id'     => $validated['agent_user_id'],
            'employe_id'        => $validated['employe_id'],
            'id_agence'         => $validated['id_agence'],
            'swap_date'         => now(),
            'battery_in_soc'    => $socIn,
            'battery_out_soc'   => $socOut,
            'swap_price'        => $prix,
            'nom'               => $validated['nom'],
            'prenom'            => $validated['prenom'],
            'phone'             => $validated['phone'],
        ]);

        DB::commit();

        // 8) Commandes BMS (hors transaction)
        try {
            $this->commandBatteryService->sendCommand($inMacId, 'charge_on');   // recharge la batterie déposée
            $this->commandBatteryService->sendCommand($outMacId, 'charge_off'); // bloque la recharge de la batterie remise
        } catch (\Throwable $e) {
            Log::warning("⚠️ Commande BMS échouée : ".$e->getMessage());
        }

        Log::info("✅ Swap OK #{$swap->id} — assoc={$ancienneAssoc->id}");

        return response()->json([
            'message'   => 'Swap effectué avec succès ✅',
            'swap_id'   => $swap->id,
            'prix'      => $prix,
            'soc_in'    => $socIn,
            'soc_out'   => $socOut,
            'syla_in'   => $sylaIn,
            'syla_out'  => $sylaOut,
        ], 201);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error("Erreur faireSwap : ".$e->getMessage());
        return response()->json([
            'message' => 'Erreur interne : '.$e->getMessage(),
        ], 500);
    }
}












    private function readSocByMac(?string $mac): ?int
    {
        if (!$mac) return null;
        $bms = BMSData::where('mac_id', $mac)->latest('id')->first();
        if (!$bms || empty($bms->state)) return null;

        try {
            $state = json_decode($bms->state, true);
            if (!isset($state['SOC']) && isset($state['SoC'])) {
                $state['SOC'] = $state['SoC'];
            }
            return max(0, min(100, (int)($state['SOC'] ?? 50)));
        } catch (\Throwable $e) {
            return 50;
        }
    }

    private function readSocByBatteryId(int $batteryId): ?int
    {
        $battery = BatteriesValide::select('mac_id')->find($batteryId);
        if (!$battery) return 50;
        return $this->readSocByMac($battery->mac_id) ?? 50;
    }

    private function calculerPourcentageConsomme($socIn, $socOut): int
    {
        return max(0, min(100, abs((int)$socOut - (int)$socIn)));
    }

    private function calculerPrixSwap(int $pourcentage): int
    {
        $prixMax = 1500;
        return (int)ceil(($pourcentage / 100) * $prixMax);
    }

// avoir le soe ou SYLA

private function readSYLAByMac(?string $mac): ?float
{
    if (!$mac) return null;
    $bms = BMSData::where('mac_id', $mac)->latest('id')->first();
    if (!$bms || empty($bms->state)) return null;

    try {
        $state = json_decode($bms->state, true);
        $syla = $state['SYLA'] ?? $state['syla'] ?? null;
        if ($syla === null) return 100.0;
        return max(0, min(100, (float) $syla));
    } catch (\Throwable $e) {
        return 100.0;
    }
}

private function readSYLAByBatteryId(int $batteryId): ?float
{
    $battery = BatteriesValide::select('mac_id')->find($batteryId);
    if (!$battery) return 100.0;
    return $this->readSYLAByMac($battery->mac_id);
}





    // historique
  
public function historiqueEmployeView($employeeId)
{
    $employee = Employe::find($employeeId);
    if (!$employee) {
        abort(404, 'Employé introuvable');
    }

    // Tous les agents (swappers) pour le filtre
    $swappers = UsersAgence::select('id', 'nom', 'prenom')->get();

    return view('swap_historique_employe', compact('employee', 'swappers'));
}


public function getHistoriqueEmployeData(Request $request, $employeeId)
{
    try {
        // ✅ Période par défaut = aujourd’hui
        $periode = $request->input('periode', 'today');
        $swapperId = $request->input('swapper_id', 'all');

        $start = null;
        $end = null;

        switch ($periode) {
            case 'today':
                $start = now()->startOfDay();
                $end   = now()->endOfDay();
                break;

            case 'yesterday':
                $start = now()->subDay()->startOfDay();
                $end   = now()->subDay()->endOfDay();
                break;

            case 'week':
                $start = now()->startOfWeek();
                $end   = now()->endOfWeek();
                break;

            case 'month':
                $start = now()->startOfMonth();
                $end   = now()->endOfMonth();
                break;

            case 'year':
                $start = now()->startOfYear();
                $end   = now()->endOfYear();
                break;

            case 'specific':
                if ($request->filled('specific_date')) {
                    $d = \Carbon\Carbon::parse($request->input('specific_date'));
                    $start = $d->startOfDay();
                    $end   = $d->endOfDay();
                }
                break;

            case 'custom':
                if ($request->filled('start_date') && $request->filled('end_date')) {
                    $start = \Carbon\Carbon::parse($request->input('start_date'))->startOfDay();
                    $end   = \Carbon\Carbon::parse($request->input('end_date'))->endOfDay();
                }
                break;
        }

        // ✅ On charge avec les relations réelles
        $query = \App\Models\Swap::with(['batteryIn', 'batteryOut', 'swappeur'])
            ->where('employe_id', $employeeId);

        // ✅ Filtre date
        if ($start && $end) {
            $query->whereBetween('swap_date', [$start, $end]);
        }

        // ✅ Filtre sur un swapper précis
        if ($swapperId !== 'all' && is_numeric($swapperId)) {
            $query->where('agent_user_id', $swapperId);
        }

        $swaps = $query->orderBy('swap_date', 'desc')->get();

        $total = (int) $swaps->sum('swap_price');
        $count = (int) $swaps->count();

        // ✅ Mapping sécurisé
        $payload = $swaps->map(function ($s) {
            $batteryIn  = optional($s->batteryIn);
            $batteryOut = optional($s->batteryOut);
            $agent      = optional($s->swappeur);

            $chauffeur = trim(($s->nom ?? '') . ' ' . ($s->prenom ?? '')) ?: '—';
            $dateStr = $s->swap_date
                ? \Carbon\Carbon::parse($s->swap_date)->format('d/m/Y H:i')
                : '—';

            return [
                'id'          => (int) $s->id,
                'swapper'     => trim(($agent->nom ?? '') . ' ' . ($agent->prenom ?? '')) ?: '—',
                'battery_in'  => $batteryIn->mac_id ?? $batteryIn->mac_id ?? '—',
                'battery_out' => $batteryOut->mac_id  ?? $batteryOut->mac_id ?? '—',
                'soc_in'      => (int) ($s->battery_in_soc ?? 0),
                'soc_out'     => (int) ($s->battery_out_soc ?? 0),
                'prix'        => (int) ($s->swap_price ?? 0),
                'chauffeur'   => $chauffeur,
                'date'        => $dateStr,
            ];
        });

        return response()->json([
            'swaps' => $payload,
            'total' => $total,
            'count' => $count,
        ]);
    } catch (\Throwable $e) {
        \Log::error("Erreur historique swap : " . $e->getMessage());
        return response()->json(['message' => 'Erreur interne : ' . $e->getMessage()], 500);
    }
}






}
