@extends('layouts.base')

@section('title', 'Faire un Swap - ProxymSwapWeb')

@section('content')
<header class="swap-hero">
  <a href="{{ route('home') }}" class="back-btn">⟵</a>
  <button id="refreshBtn" title="Rafraîchir les données">⟳</button>
  <h1>🔁 Faire un Swap</h1>
  <p>Effectuez un échange rapide et sûr entre batteries.</p>
</header>

<main class="swap-main">
  <!-- Sélection Station -->
  <div class="form-group">
    <label for="station">Station / Agence</label>
    <select id="station" class="input-select">
      <option value="">Sélectionnez une station</option>
      @foreach ($stations as $station)
        <option value="{{ $station->id }}">{{ $station->nom_agence }} ({{ $station->ville }})</option>
      @endforeach
    </select>
  </div>

  <!-- Sélection Agent -->
  <div class="form-group">
    <label for="agent">Agent de Swap</label>
    <select id="agent" class="input-select" disabled>
      <option value="">Choisir une station d’abord</option>
    </select>
  </div>

  <!-- Batterie du chauffeur -->
  <div class="form-group">
    <label for="currentBattery">Batterie du chauffeur</label>
    <input type="text" id="currentBattery" class="input-text" placeholder="Entrez les 6 derniers chiffres du MAC ID">
  </div>

  <div class="info-card" id="driverInfo">
    <p><strong>Chauffeur :</strong> —</p>
    <p><strong>Téléphone :</strong> —</p>
    <p><strong>Moto (VIN) :</strong> —</p>
    <p><strong>Batterie actuelle :</strong> —</p>
    <p><strong>Charge :</strong> —</p>
  </div>

   <!-- Prix -->
  <div class="price-card" id="priceInfo" style="display:none;">
    💰 <strong>Prix estimé :</strong>
    <span id="swapPrice" style="color:var(--brand); font-weight:700;">0 FCFA</span>
  </div>

  <!-- Recherche batterie -->
  <div class="form-group">
    <label for="batterySearch">🔍 Rechercher une batterie</label>
    <input type="text" id="batterySearch" class="input-text" placeholder="Recherche par MAC ID...">
  </div>

  <h2 class="section-title">🔋 Batteries disponibles</h2>
  <div class="battery-scroll">
    <div class="battery-grid" id="batteryGrid">
      <p style="text-align:center; color:#888;">Sélectionnez une station...</p>
    </div>
  </div>

  <!-- Validation -->
  <div class="form-actions">
    <button class="btn-validate" id="validateBtn">✅ Valider le Swap</button>
  </div>
</main>



<style>
:root {
  --bg: #101010;
  --text: #F3F3F3;
  --brand: #DCDB32;
  --card: #1B1B1B;
  --border: #2a2a2a;
}
body {
  background: var(--bg);
  color: var(--text);
  font-family: 'Nunito', sans-serif;
  margin: 0;
}
.swap-hero {
  background: var(--brand);
  color: #101010;
  text-align: center;
  padding: 25px 10px;
  position: relative;
  box-shadow: 0 3px 8px rgba(0,0,0,0.3);
}
.back-btn, #refreshBtn {
  position: absolute; top: 12px;
  background: var(--brand); color: var(--bg);
  border: none; border-radius: 10px;
  padding: 8px 12px; font-size: 1.2rem;
  cursor: pointer;
  text-decoration:none;
  font-size: 2rem;
  font-style: bold;
  font: arial; 
  
}
.back-btn { left: 12px; }
#refreshBtn { right: 12px; }
.swap-main { padding: 25px 20px 50px; }
.form-group { margin-bottom: 20px; }
label { color: var(--brand); font-weight: 700; margin-bottom: 8px; display:block; }
.input-text, .input-select {
  width: 100%; background: var(--card); color: var(--text);
  border: none; border-radius: 10px;
  padding: 14px 16px; font-size: 1rem;
  box-shadow: inset 0 0 0 1px var(--border);
}
.info-card, .price-card {
  background: var(--card); padding: 16px;
  border-radius: 12px; margin-bottom: 20px;
}
.section-title { color: var(--brand); text-align:center; margin:20px 0 10px; }
.battery-scroll {
  max-height: 300px;
  overflow-y: auto;
  padding-right: 5px;
}
.battery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 14px; }
.battery-card {
  background: var(--card);
  border-radius: 14px; padding: 14px;
  text-align: center; cursor: pointer;
  transition: all 0.3s;
}
.battery-card.selected { border: 1px solid var(--brand); background: rgba(220,219,50,0.08); }
.progress-container { background: #2a2a2a; border-radius: 10px; height: 10px; overflow: hidden; margin-top: 8px; }
.progress-bar { height: 100%; border-radius: 10px; transition: width 0.4s; }
.btn-validate {
  background: var(--brand);
  color: #101010;
  font-weight: 700;
  padding: 14px 24px;
  border: none;
  border-radius: 10px;
  width: 100%;
  max-width: 320px;
  cursor: pointer;
  transition: transform .1s ease, opacity .2s ease;
}
.btn-validate:active {
  transform: scale(0.95);
  opacity: 0.8;
}
.btn-validate.disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* ✅ Popup au centre */
.popup {
  position: fixed;
  top: 50%; left: 50%;
  transform: translate(-50%, -50%) scale(0.9);
  background: #1b1b1b;
  color: #fff;
  border-radius: 16px;
  padding: 30px 40px;
  text-align: center;
  box-shadow: 0 4px 15px rgba(0,0,0,0.5);
  z-index: 9999;
  opacity: 0;
  transition: all 0.4s ease;
}
.popup.show {
  opacity: 1;
  transform: translate(-50%, -50%) scale(1);
}
.popup.success { background: #0f5132; }
.popup.error { background: #842029; }
.popup-icon {
  font-size: 3rem;
  margin-bottom: 10px;
  animation: popscale 0.7s ease;
}
@keyframes popscale {
  0% { transform: scale(0.2); opacity: 0; }
  60% { transform: scale(1.2); opacity: 1; }
  100% { transform: scale(1); }
}
</style>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const stationSelect = document.getElementById("station");
  const agentSelect = document.getElementById("agent");
  const batteryGrid = document.getElementById("batteryGrid");
  const batterySearch = document.getElementById("batterySearch");
  const currentBatteryInput = document.getElementById("currentBattery");
  const driverInfo = document.getElementById("driverInfo");
  const priceCard = document.getElementById("priceInfo");
  const priceDisplay = document.getElementById("swapPrice");
  const refreshBtn = document.getElementById("refreshBtn");
  const validateBtn = document.getElementById("validateBtn");
  const popup = document.getElementById("popup");
  const popupIcon = document.getElementById("popupIcon");
  const popupMsg = document.getElementById("popupMsg");
  const EMPLOYE_ID = {{ Auth::user()->id }};

  let chauffeurData = null;
  let selectedBattery = null;
  let allBatteries = [];

  function showPopup(type, message) {
    popup.className = `popup ${type} show`;
    popupIcon.innerHTML = type === "success" ? "✅" : "❌";
    popupMsg.textContent = message;
    setTimeout(() => popup.classList.remove("show"), 2500);
  }

  // ✅ Charger agents + batteries automatiquement à la sélection d’une station
  stationSelect.addEventListener("change", async () => {
    const id = stationSelect.value;
    if (!id) {
      agentSelect.innerHTML = "<option>Choisir une station d’abord</option>";
      agentSelect.disabled = true;
      batteryGrid.innerHTML = "<p style='text-align:center;color:#888;'>Sélectionnez une station...</p>";
      return;
    }

    agentSelect.innerHTML = "<option>Chargement des agents...</option>";
    agentSelect.disabled = true;
    batteryGrid.innerHTML = "<p style='color:#888;text-align:center;'>Chargement des batteries...</p>";

    try {
      const aRes = await fetch(`/swap/agents/${id}`);
      const agents = await aRes.json();
      agentSelect.innerHTML = "<option value=''>Sélectionnez un agent</option>";
      agents.forEach(a => agentSelect.innerHTML += `<option value="${a.id}">${a.nom} ${a.prenom}</option>`);
      agentSelect.disabled = false;

      const bRes = await fetch(`/swap/batteries/${id}`);
      allBatteries = await bRes.json();
      allBatteries.sort((a,b)=>(b.soc||0)-(a.soc||0));
      displayBatteries(allBatteries);
    } catch {
      showPopup("error", "Erreur de chargement");
    }
  });

  batterySearch.addEventListener("input", e => {
    const val = e.target.value.trim().toLowerCase();
    const filtered = allBatteries.filter(b => b.mac_id.toLowerCase().includes(val));
    displayBatteries(filtered);
  });

  function displayBatteries(bats){
    batteryGrid.innerHTML = bats.length
      ? bats.map(b=>{
          const color=b.soc>=90?"#4caf50":b.soc>=60?"#ff9800":"#f44336";
          return `<div class="battery-card" data-id="${b.id}">
                    <p><strong>${b.mac_id}</strong></p>
                    <div class='progress-container'>
                      <div class='progress-bar' style='width:${b.soc}%;background:${color}'></div>
                    </div>
                    <p style='font-size:0.9rem;color:#bbb;'>${b.soc}%</p>
                  </div>`;
      }).join('')
      : "<p style='text-align:center;color:#777;'>Aucune batterie trouvée.</p>";

    document.querySelectorAll('.battery-card').forEach(div=>{
      div.onclick=()=>{
        document.querySelectorAll('.battery-card').forEach(x=>x.classList.remove('selected'));
        div.classList.add('selected');
        selectedBattery=allBatteries.find(b=>b.id==div.dataset.id);
        updatePrice();
      };
    });
  }

  currentBatteryInput.addEventListener("input", async ()=>{
    const mac=currentBatteryInput.value.trim();
    if(mac.length<6)return;
    driverInfo.innerHTML="<p style='color:#aaa;'>Recherche...</p>";
    try{
      const r=await fetch(`/swap/chauffeur/${mac}`);
      const d=await r.json(); chauffeurData=d;
      driverInfo.innerHTML=`
        <p><strong>Chauffeur :</strong> ${d.chauffeur_nom} ${d.chauffeur_prenom}</p>
        <p><strong>Téléphone :</strong> ${d.chauffeur_phone}</p>
        <p><strong>Moto (VIN) :</strong> ${d.moto_vin}</p>
        <p><strong>Batterie :</strong> ${d.mac_id_complet}</p>
        <p><strong>Charge :</strong> ${d.soc}%</p>`;
      updatePrice();
    }catch{showPopup("error","Chauffeur introuvable.");}
  });

  async function updatePrice(){
    if(!chauffeurData||!selectedBattery)return;
    try{
      const r=await fetch("/swap/prix",{
        method:"POST",
        headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name=\"csrf-token\"]').content},
        body:JSON.stringify({battery_out_id:selectedBattery.id,battery_in_id:chauffeurData.id_battery_valide})
      });
      const j=await r.json();
      priceDisplay.textContent=`${j.prix} FCFA`;
      priceCard.style.display='block';
    }catch{priceCard.style.display='none';}
  }

  validateBtn.onclick = async () => {
    if(validateBtn.classList.contains("disabled")) return;
    validateBtn.classList.add("disabled");
    validateBtn.textContent = "⏳ Traitement...";

    const st = stationSelect.value, ag = agentSelect.value;
    if(!chauffeurData || !selectedBattery || !st || !ag){
      showPopup("error","Veuillez remplir tous les champs");
      validateBtn.classList.remove("disabled");
      validateBtn.textContent = "✅ Valider le Swap";
      return;
    }

    const prix = parseInt(priceDisplay.textContent.replace(" FCFA","")) || 0;

    try {
      const r = await fetch("/swap", {
        method:"POST",
        headers:{"Content-Type":"application/json","X-CSRF-TOKEN":document.querySelector('meta[name=\"csrf-token\"]').content},
        body:JSON.stringify({
          battery_out_id:selectedBattery.id,
          battery_in_id:chauffeurData.id_battery_valide,
          agent_user_id:ag,
          id_agence:st,
          nom:chauffeurData.chauffeur_nom,
          prenom:chauffeurData.chauffeur_prenom,
          phone:chauffeurData.chauffeur_phone,
          swap_price: prix,
          employe_id: EMPLOYE_ID
        })
      });
      const j = await r.json();
      if(!r.ok) throw new Error(j.message);
      showPopup("success","Swap effectué avec succès !");
      setTimeout(()=>location.reload(),4000);
    }catch(e){
      showPopup("error", e.message || "Erreur interne");
      validateBtn.classList.remove("disabled");
      validateBtn.textContent = "✅ Valider le Swap";
    }
  };

  refreshBtn.onclick=()=>location.reload();
});
</script>
@endsection
