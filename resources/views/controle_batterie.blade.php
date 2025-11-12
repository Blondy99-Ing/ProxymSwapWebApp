@extends('layouts.base')

@section('title', 'Contrôle Batteries - ProxymSwapWeb')

@section('content')
<header class="hero">
    <a href="{{ route('home') }}" class="back-btn">⟵</a>
    <h1>🔋 Contrôle des Batteries</h1>
    <p>Gérez la charge et la décharge des batteries connectées.</p>
</header>

<main class="home-main">
    {{-- Barre de recherche --}}
    <div class="search-container">
        <input type="text" id="searchInput" placeholder="Rechercher une MAC ID..." onkeyup="filterBatteries()">
    </div>

    <section class="battery-grid" id="batteryGrid">
        <p id="loadingMessage">Chargement des batteries...</p>
        {{-- Les cartes dynamiques seront insérées ici --}}
    </section>
</main>
{{-- Popup Notification pour les résultats de commande --}}
<div id="notificationPopup" class="notification"></div>

{{-- Modal HTML --}}
<div id="confirmModal" class="modal">
    <div class="modal-content">
        <h2 id="modalTitle">Confirmer l’action</h2>
        <p id="modalMsg"></p>
        <div class="modal-actions">
            <button id="btnCancel" class="btn cancel">Annuler</button>
            <button id="btnConfirm" class="btn confirm">Confirmer</button>
        </div>
    </div>
</div>


{{-- Styles --}}
<style>
:root {
    --bg: #101010;
    --text: #F3F3F3;
    --brand: #DCDB32;
    --card: #1B1B1B;
}

/* ==== STRUCTURE ==== */
body {
    background-color: var(--bg);
    color: var(--text);
    font-family: 'Arial', sans-serif;
    margin: 0;
}
/* ==== SEARCH BAR ==== */
.search-container {
    width: 100%;
    padding: 0 1rem;
    margin-bottom: 1rem;
}

#searchInput {
    width: 100%;
    padding: 10px 14px;
    border-radius: 8px;
    border: none;
    font-size: 1rem;
    background: var(--card);
    color: var(--text);
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.81);
}

#searchInput::placeholder {
    color: #888;
}

/* ==== GRID ==== */
.battery-grid {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding: 0 1rem;
}

/* ==== CARDS ==== */
.battery-card {
    background: var(--card);
    border-radius: 12px;
    padding: 12px 16px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: transform 0.2s, box-shadow 0.2s;
}

.battery-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.6);
}

.card-left {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.mac-id {
    font-weight: 700;
    font-size: 2rem;
    color: var(--brand);
}

/* Conteneur pour aligner les status horizontalement */
.status-container {
    display: flex;
    gap: 10px; /* espace entre les deux status */
    margin-bottom: 5px; /* Réduit l'espace */
}

.status {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
}

.indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: red;
    border: 1px solid #333;
    transition: background 0.3s;

}

.indicator.active.charge {
    background: #00C853; /* Vert pour Charge ON */
}

.indicator.active.discharge {
    background: #FFD600; /* Jaune/Orange pour Décharge ON */
}

/* ==== BOUTONS ==== */
.card-right {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.btn-toggle {
    background: var(--brand);
    color: var(--text);
    border: none;
    padding: 6px 12px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s, transform 0.2s;
}

.btn-toggle:hover {
    background: #c4c328;
    transform: translateY(-1px);
}
.btn-toggle[data-state="on"] {
    background-color: #d83d3d; /* Rouge si l'action est déjà ON (pour le bouton OFF) */
}


/* ==== MODAL ==== */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.6);
    justify-content: center;
    align-items: center;
}

.modal.show {
    display: flex;
}

.modal-content {
    background: var(--card);
    color: var(--text);
    padding: 2rem;
    border-radius: 12px;
    width: 90%;
    max-width: 400px;
    text-align: center;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.7);
    animation: fadeIn 0.3s ease;
}

.modal-content h2 {
    margin-bottom: 0.5rem;
    color: var(--brand);
}

.modal-actions {
    margin-top: 1.5rem;
    display: flex;
    justify-content: center;
    gap: 12px;
}

.btn {
    padding: 8px 16px;
    border-radius: 10px;
    font-weight: 600;
    border: none;
    cursor: pointer;
}

.btn.cancel {
    background: #555;
    color: var(--text);
}

.btn.confirm {
    background: var(--brand);
    color: var(--text);
}





/* ==== NOTIFICATION POPUP (Optionnel : si elle manque) ==== */
.notification {
    display: none;
    position: fixed;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    padding: 12px 20px;
    border-radius: 8px;
    color: var(--text);
    font-weight: 600;
    z-index: 1001;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
    opacity: 0;
    transition: opacity 0.5s ease-in-out;
}

.notification.show {
    opacity: 1;
    display: block;
}

.notification.success {
    background-color: #00C853; /* Vert */
}

.notification.error {
    background-color: #d83d3d; /* Rouge */
}

.notification.warning {
    background-color: #FFD600; /* Jaune */
    color: #101010;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }

    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* Responsive */
@media (max-width: 500px) {
    .battery-card {
        flex-direction: column;
        align-items: flex-start;
    }

    .card-right {
        width: 100%;
        justify-content: space-between;
    }

    .btn-toggle {
        width: 48%;
        margin-top: 4px;
    }
}
</style>
<script>
    // =========================================================================
    // VARIABLES GLOBALES
    // =========================================================================
    let selectedBattery = null;
    let selectedActionType = null; // Ex: 'charge_on', 'discharge_off'
    let isTurningOn = false;

    // Données provenant du Controller
    // NOTE: Assurez-vous que la variable $batteriesMacIds est toujours définie dans votre contrôleur Laravel.
    const MAC_IDS = @json($batteriesMacIds); 
    const GRID = document.getElementById('batteryGrid');
    const TOKEN = document.querySelector('meta[name="csrf-token"]').content;

    // Variables pour le Polling (Vérification du statut après commande)
    const POLLING_INTERVAL = 5000;  // Vérifie toutes les 5 secondes
    const POLLING_DURATION = 45000; // Dure 45 secondes au maximum


    // =========================================================================
    // FONCTION D'AFFICHAGE DE POPUP NON BLOQUANTE (NÉCESSAIRE)
    // =========================================================================

    /**
     * Affiche une notification non bloquante en bas de l'écran.
     * @param {string} type - 'success', 'error', 'warning'.
     * @param {string} message - Le message à afficher (supporte **gras**).
     */
    function showPopup(type, message) {
        const popup = document.getElementById('notificationPopup');
        
        if (!popup) {
            console.error('L\'élément #notificationPopup est manquant dans le HTML. Utilisant alert() à la place.');
            alert(`${type.toUpperCase()}: ${message.replace(/\*\*/g, '')}`);
            return;
        }

        // Formater le message (remplace **gras** par <strong>)
        const formattedMessage = message.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        
        // Nettoyage de l'état précédent
        popup.className = 'notification';
        
        // Configuration
        popup.classList.add(type, 'show');
        popup.innerHTML = formattedMessage;

        // Masquer après 5 secondes
        clearTimeout(popup.timer);
        popup.timer = setTimeout(() => {
            popup.classList.remove('show');
        }, 5000);
    }

    // =========================================================================
    // 1. RENDU DYNAMIQUE ET MISE À JOUR
    // =========================================================================

    /**
     * Crée le HTML pour une carte batterie.
     * @param {object} data - Les données du statut de la batterie.
     * @returns {string} Le HTML de la carte.
     */
    function createBatteryCard(data) {
        const { mac_id, soc, is_charging_on, is_discharging_on } = data;

        // Statut Charge
        const chargeStatusText = is_charging_on ? 'ON' : 'OFF';
        const chargeIndicatorClass = is_charging_on ? 'active charge' : 'charge';
        const chargeButtonText = is_charging_on ? 'Éteindre Charge 🚫' : 'Allumer Charge ⚡';
        const chargeAction = is_charging_on ? 'charge_off' : 'charge_on';
        const chargeColor = is_charging_on ? '#00C853' : 'red';

        // Statut Décharge
        const dischargeStatusText = is_discharging_on ? 'ON' : 'OFF';
        const dischargeIndicatorClass = is_discharging_on ? 'active discharge' : 'discharge';
        const dischargeButtonText = is_discharging_on ? 'Éteindre Décharge 🚫' : 'Allumer Décharge 🔋';
        const dischargeColor = is_discharging_on ? '#FFD600' : 'red';
        const dischargeAction = is_discharging_on ? 'discharge_off' : 'discharge_on';


        return `
            <div class="battery-card" data-mac-id="${mac_id}">
                <div class="card-left">
                    <h3 class="mac-id">${mac_id}</h3>
                    <p style="margin: 0; font-size: 1.1rem; color: #fff;">SOC : <strong>${soc}%</strong></p>
                    <div class="status-container">
                        <div class="status">
                            <div class="indicator ${chargeIndicatorClass}"></div>
                            <span>Charge : <strong style="color: ${chargeColor};">${chargeStatusText}</strong></span>
                        </div>
                        <div class="status">
                            <div class="indicator ${dischargeIndicatorClass}"></div>
                            <span>Décharge : <strong style="color: ${dischargeColor};">${dischargeStatusText}</strong></span>
                        </div>
                    </div>
                </div>
                <div class="card-right">
                    <button class="btn-toggle charge" data-state="${is_charging_on ? 'on' : 'off'}"
                        onclick="confirmAction('${mac_id}', 'charge', '${chargeAction}')">
                        ${chargeButtonText}
                    </button>
                    <button class="btn-toggle discharge" data-state="${is_discharging_on ? 'on' : 'off'}"
                        onclick="confirmAction('${mac_id}', 'discharge', '${dischargeAction}')">
                        ${dischargeButtonText}
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Met à jour UNE SEULE carte de batterie après une commande.
     * @param {string} macId
     * @param {object} data - Les données de statut mises à jour.
     */
    function updateBatteryCard(macId, data) {
        const existingCard = GRID.querySelector(`[data-mac-id="${macId}"]`);
        
        if (existingCard) {
            const newCardHtml = createBatteryCard(data);
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = newCardHtml.trim();
            const newCard = tempDiv.firstChild;
            
            GRID.replaceChild(newCard, existingCard);
        } else {
             // Si la carte n'existe pas, on l'ajoute.
             const newCardHtml = createBatteryCard(data);
             const tempDiv = document.createElement('div');
             tempDiv.innerHTML = newCardHtml.trim();
             GRID.appendChild(tempDiv.firstChild);
        }
    }


    /**
     * Appelle l'API pour récupérer les données d'UNE batterie.
     * @param {string} macId
     * @returns {object|null} Les données de statut si succès, sinon null.
     */
    async function fetchBatteryStatus(macId) {
        const url = `/batteries/api/status/${macId}`;
        
        try {
            const response = await fetch(url);
            if (!response.ok) {
                 // Si l'API renvoie une erreur (404 si données absentes)
                const errorResult = await response.json();
                console.error(`Statut non disponible pour ${macId}:`, errorResult.message);
                return null;
            }
            return await response.json();

        } catch (error) {
            console.error(`Erreur réseau pour ${macId}:`, error);
            return null;
        }
    }

    /**
     * Charge les statuts de toutes les batteries en batch au démarrage de la page.
     */
    async function loadAllBatteriesStatus() {
        GRID.innerHTML = '';
        const loadingMessage = document.getElementById('loadingMessage');
        if (loadingMessage) loadingMessage.style.display = 'block';

        const url = `/batteries/api/status/all`; 
        
        try {
            const response = await fetch(url);
            const result = await response.json();

            if (response.ok && Array.isArray(result.data)) {
                if (result.data.length === 0) {
                     GRID.innerHTML = '<p>Aucune batterie valide trouvée.</p>';
                } else {
                    result.data.forEach(data => {
                        updateBatteryCard(data.mac_id, data);
                    });
                }
            } else {
                GRID.innerHTML = `<p style="color:red;">Erreur de l'API de statut: ${result.message || 'Problème inconnu.'}</p>`;
            }
            
        } catch (error) {
            console.error('Erreur lors du chargement des statuts en batch:', error);
            GRID.innerHTML = '<p style="color:red;">Erreur réseau lors de la récupération des statuts.</p>';
        } finally {
            if (loadingMessage) loadingMessage.style.display = 'none';
        }
    }


    // =========================================================================
    // 2. GESTION DU POLLING ET DE LA COMMANDE
    // =========================================================================

    /**
     * Lance le Polling pour vérifier que le statut dans la DB locale a été mis à jour.
     * @param {string} macId
     * @param {string} expectedActionType ('charge_on', 'discharge_off', etc.)
     */
    async function pollStatus(macId, expectedActionType) {
        // NOTE: On utilise 'card' pour l'indicateur visuel initial sur l'ancienne carte
        const card = GRID.querySelector(`[data-mac-id="${macId}"]`);
        
        // Indicateur visuel de polling
        if (card) {
            card.style.opacity = '0.7';
            card.style.boxShadow = '0 0 15px 5px rgba(220, 219, 50, 0.7)'; // Effet lumineux
        }

        const isExpectedOn = expectedActionType.endsWith('_on');
        const statusKey = expectedActionType.startsWith('charge') ? 'is_charging_on' : 'is_discharging_on';

        const pollingStartTime = Date.now();
        let confirmed = false;

        const intervalId = setInterval(async () => {
            const elapsedTime = Date.now() - pollingStartTime;
            
            if (elapsedTime > POLLING_DURATION) {
                clearInterval(intervalId);
                showPopup('warning', `Le statut de la batterie **${macId}** n'a pas été confirmé après 45 secondes. Vérifiez la connexion BMS.`);
                // Réinitialiser la carte même en cas d'échec du polling
                if (card) {
                    card.style.opacity = '1';
                    card.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.5)';
                }
                return;
            }

            try {
                const result = await fetchBatteryStatus(macId);

                if (result) {
                    const currentStatus = result[statusKey];
                    
                    // Si le statut actuel dans la DB correspond au statut attendu
                    if ((isExpectedOn && currentStatus === true) || (!isExpectedOn && currentStatus === false)) {
                        
                        // Succès : Le statut est mis à jour
                        clearInterval(intervalId);
                        confirmed = true;
                        
                        // Mettre à jour la carte (remplace l'élément DOM)
                        updateBatteryCard(macId, result);

                        // ⭐ CORRECTION CLÉ : Récupérer la NOUVELLE référence de la carte après le remplacement
                        const updatedCard = GRID.querySelector(`[data-mac-id="${macId}"]`);
                        
                        // Afficher le message final
                        showPopup('success', `Statut de **${macId}** confirmé comme étant **${isExpectedOn ? 'ON' : 'OFF'}**. (Après ${Math.round(elapsedTime / 1000)}s)`);
                        
                        // Enlever l'indicateur de polling sur la NOUVELLE CARTE pour la rendre interactive
                        if (updatedCard) { 
                            updatedCard.style.opacity = '1';
                            updatedCard.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.5)';
                        }
                    }
                }
            } catch (error) {
                // Les erreurs de réseau/API sont gérées dans fetchBatteryStatus
            }

        }, POLLING_INTERVAL);
    }


    /**
     * Envoie la commande BMS via l'API.
     */
    async function sendCommand() {
        if (!selectedBattery || !selectedActionType) return;

        // Stocker les valeurs nécessaires avant le reset du finally
        const macId = selectedBattery;
        const actionType = selectedActionType; 
        
        const url = `/batteries/api/command`; 
        
        try {
            const card = GRID.querySelector(`[data-mac-id="${macId}"]`);
            // Visuel de commande en cours
            if (card) card.style.opacity = '0.5';

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': TOKEN,
                },
                body: JSON.stringify({
                    mac_id: macId,
                    action: actionType
                })
            });

            const result = await response.json();

            if (response.ok && result.success) {
                // Commande envoyée avec succès -> Lancer le Polling
                showPopup('success', result.message + " **Début de la vérification du statut...**");
                pollStatus(macId, actionType);

            } else {
                // Échec de l'API de commande : réactiver la carte
                const errorMsg = result.message || "Erreur de communication avec l'API de commande.";
                showPopup('error', errorMsg);
                if (card) card.style.opacity = '1';
            }

        } catch (error) {
            console.error('Erreur lors de l\'envoi de la commande:', error);
            showPopup('error', `Erreur réseau : Impossible d'atteindre le serveur.`);
             // En cas d'erreur réseau, réactiver la carte
             const card = GRID.querySelector(`[data-mac-id="${macId}"]`);
             if (card) card.style.opacity = '1';
        } finally {
            // Réinitialiser les variables de la modal
            selectedBattery = null;
            selectedActionType = null;
        }
    }


    // =========================================================================
    // 3. LISTENERS ET MODAL
    // =========================================================================

    /**
     * Ouvre la modal de confirmation.
     */
    function confirmAction(macId, actionType, commandAction) {
        // Empêcher l'ouverture si une action est déjà en cours (polling)
        if (GRID.querySelector(`[data-mac-id="${macId}"]`).style.opacity === '0.7') {
             showPopup('warning', `Commande en cours pour **${macId}**. Veuillez patienter.`);
             return;
        }

        selectedBattery = macId;
        selectedActionType = commandAction;
        isTurningOn = commandAction.includes('_on');

        const label = actionType === 'charge' ? 'charge' : 'décharge';
        const actionWord = isTurningOn ? 'Allumer' : 'Éteindre';

        const modal = document.getElementById('confirmModal');
        document.getElementById('modalTitle').textContent = 'Confirmer l’action';
        document.getElementById('modalMsg').innerHTML =
            `**${actionWord}** la ${label} pour la batterie **${macId}** ?`;

        modal.classList.add('show');
    }

    // Fermeture de la modal (Annuler)
    document.getElementById('btnCancel').onclick = () => {
        // Nettoyer les variables si l'utilisateur annule
        selectedBattery = null;
        selectedActionType = null;
        document.getElementById('confirmModal').classList.remove('show');
    };

    // Confirmation de la modal -> envoi de la commande
    document.getElementById('btnConfirm').onclick = () => {
        // Masquer la modale juste avant d'envoyer la commande
        document.getElementById('confirmModal').classList.remove('show');
        sendCommand();
    };

    // Fonction de recherche (filtrage)
    function filterBatteries() {
        const input = document.getElementById('searchInput').value.toUpperCase();
        const cards = GRID.getElementsByClassName('battery-card');

        for (let i = 0; i < cards.length; i++) {
            const mac = cards[i].getAttribute('data-mac-id').toUpperCase();
            cards[i].style.display = mac.includes(input) ? 'flex' : 'none';
        }
    }

    // Lance le chargement des données au démarrage
    window.onload = loadAllBatteriesStatus;
</script>
@endsection