<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CommandBatteryService
{
    protected string $baseUrl = 'http://api.mtbms.com/api.php/ibms';
    protected ?string $token = null;

    /**
     * Authentifie le système et récupère le token (mds)
     */
    protected function getToken(): ?string
    {
        if ($this->token) {
            return $this->token; // Utiliser le token déjà chargé
        }

        try {
            $response = Http::get("{$this->baseUrl}/loginSystem", [
                'LoginName' => 'D13',
                'LoginPassword' => 'QC123456',
                'LoginType' => 'ENTERPRISE',
                'language' => 'cn',
                'ISMD5' => 0,
                'timeZone' => '+08',
                'apply' => 'APP',
            ]);

            if ($response->successful() && isset($response->json()['mds'])) {
                $this->token = $response->json()['mds'];
                return $this->token;
            }

            Log::error('BMSService: Impossible d’obtenir le token', ['response' => $response->body()]);
            return null;

        } catch (\Exception $e) {
            Log::error('BMSService: Erreur lors de la récupération du token', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Envoie une commande (charge_on / charge_off / discharge_on / discharge_off)
     */
    public function sendCommand(string $macId, string $action): array
    {
        $token = $this->getToken();

        if (!$token) {
            return ['success' => false, 'error' => 'Impossible de récupérer le token BMS'];
        }

        // Traduction des actions en codes hexadécimaux
        $param = match ($action) {
            'charge_on'     => 'E20000000B000400160101',
            'charge_off'    => 'E20000000B000400160100',
            'discharge_on'  => 'E20000000B000400170101',
            'discharge_off' => 'E20000000B000400170100',
            default         => null,
        };

        if (!$param) {
            return ['success' => false, 'error' => "Action invalide : {$action}"];
        }

        try {
            $response = Http::post("{$this->baseUrl}/getDateFunc", [
                'method' => 'SendCommands',
                'mds' => $token,
                'macid' => $macId,
                'cmd' => 'MTS_BMS_SETTING',
                'param' => $param,
                'pwd' => '',
            ]);

            if ($response->successful() && isset($response->json()['data'][0]['CmdNo'])) {
                $cmdNo = $response->json()['data'][0]['CmdNo'];

                Log::info("BMSService: Commande {$action} envoyée avec succès", [
                    'mac_id' => $macId,
                    'cmdNo' => $cmdNo,
                ]);

                return [
                    'success' => true,
                    'message' => "Commande {$action} envoyée avec succès",
                    'cmdNo' => $cmdNo,
                ];
            }

            $error = $response->json()['errorDescribe'] ?? 'Erreur inconnue';
            Log::warning('BMSService: Échec de la commande', [
                'action' => $action,
                'mac_id' => $macId,
                'error' => $error,
            ]);

            return ['success' => false, 'error' => $error];

        } catch (\Exception $e) {
            Log::error('BMSService: Exception lors de l’envoi de commande', [
                'message' => $e->getMessage(),
                'action' => $action,
                'mac_id' => $macId,
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
