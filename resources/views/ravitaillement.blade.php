@extends('layouts.base')

@section('title', 'Ravitaillement des Stations - ProxymSwapWeb')

@section('content')
<link rel="stylesheet" href="{{ asset('css/ravitaillement_style.css') }}">
<header class="swap-hero">
  <a href="{{ route('home') }}" class="back-btn">⟵</a>
  <h1>⚡ Ravitaillement des Stations</h1>
  <p>Ajoutez ou retirez plusieurs batteries dans une agence.</p>
</header>

<main class="swap-main">
  <!-- Sélection Station -->
  <div class="form-group">
    <label for="stationSelect">🚉 Sélectionnez une agence</label>
    <select id="stationSelect" class="input-select">
      <option value="">Choisir une station...</option>
      @foreach ($stations as $s)
        <option value="{{ $s->id }}" data-name="{{ $s->nom_agence }}">
          {{ $s->nom_agence }} ({{ $s->ville }})
        </option>
      @endforeach
    </select>
  </div>

  <!-- Batteries globales -->
  <section class="battery-section">
    <h2 class="section-title">🔋 Batteries disponibles (système global)</h2>
    <input type="text" id="searchGlobal" class="search-input" placeholder="🔍 Rechercher une batterie globale...">

    <div class="battery-scroll">
      <div class="battery-grid" id="globalBatteries"></div>
    </div>
  </section>

  <!-- Batteries station -->
  <section class="battery-section">
    <h2 class="section-title">🏠 Batteries de la station sélectionnée</h2>
    <input type="text" id="searchStation" class="search-input" placeholder="🔍 Rechercher une batterie dans cette station...">

    <div class="battery-scroll">
      <div class="battery-grid" id="stationBatteries"></div>
    </div>
  </section>

  <!-- Validation -->
  <div class="form-actions">
    <button class="btn-validate" id="validateBtn">✅ Valider le Ravitaillement</button>
  </div>
</main>

<!-- ================= MODALE ================= -->
<div class="modal-overlay" id="confirmOverlay" aria-hidden="true">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="modal-header">
      <h3>Confirmation du ravitaillement</h3>
      <button class="modal-close" id="modalClose" aria-label="Fermer">✕</button>
    </div>
    <div class="modal-body">
      <div class="agency-block">
        <p class="agency-title">Agence sélectionnée</p>
        <p class="agency-value"><span id="confirmAgencyName">—</span> <small>(ID: <span id="confirmAgencyId">—</span>)</small></p>
      </div>

      <div class="lists">
        <div class="list-col">
          <h4>À AJOUTER <span class="count-badge" id="addCount">0</span></h4>
          <div class="list-scroll" id="addList"></div>
        </div>
        <div class="list-col">
          <h4>À RETIRER <span class="count-badge danger" id="removeCount">0</span></h4>
          <div class="list-scroll" id="removeList"></div>
        </div>
      </div>
    </div>

    <div class="modal-footer">
      <button class="btn ghost" id="cancelBtn">Annuler</button>
      <button class="btn primary" id="confirmBtn">Confirmer</button>
    </div>
  </div>
</div>



<script>
document.addEventListener("DOMContentLoaded", async () => {
  const stationSelect = document.getElementById("stationSelect");
  const globalContainer = document.getElementById("globalBatteries");
  const stationContainer = document.getElementById("stationBatteries");

  let allBatteries = [];
  let stationBatteries = [];

  // =========================
  // 🔹 CHARGEMENT INITIAL
  // =========================
  await loadGlobalBatteries();

  // =========================
  // 🔹 Quand on change d’agence
  // =========================
  stationSelect.addEventListener("change", async (e) => {
    const id = e.target.value;
    if (!id) {
      stationContainer.innerHTML = "";
      return;
    }
    await loadStationBatteries(id);
  });

  // =========================
  // 🔹 Chargement global
  // =========================
  async function loadGlobalBatteries() {
    globalContainer.innerHTML = "<p>⏳ Chargement...</p>";
    try {
      const res = await fetch("/ravitaillement/batteries");
      const data = await res.json();
      allBatteries = data;
      renderBatteries(globalContainer, allBatteries, "add");
    } catch {
      globalContainer.innerHTML = "<p>❌ Erreur de chargement.</p>";
    }
  }

  // =========================
  // 🔹 Chargement station
  // =========================
  async function loadStationBatteries(id) {
    stationContainer.innerHTML = "<p>⏳ Chargement...</p>";
    try {
      const res = await fetch(`/ravitaillement/batteries/${id}`);
      const data = await res.json();
      stationBatteries = data;
      renderBatteries(stationContainer, stationBatteries, "remove", true);
    } catch {
      stationContainer.innerHTML = "<p>❌ Erreur de chargement.</p>";
    }
  }

  // =========================
  // 🔹 Fonction de rendu
  // =========================
  function renderBatteries(container, list, type, isStation = false) {
    container.innerHTML = list.map(b => `
      <label class="battery-card ${isStation ? 'station' : ''}" data-mac="${b.mac_id}">
        <input type="checkbox" class="battery-checkbox" data-type="${type}">
        <div class="battery-info">
          <div class="battery-header">
            <p class="battery-mac">${b.mac_id}</p>
            <span class="battery-status ${isStation ? 'offline' : 'online'}">${isStation ? '🟥' : '🟢'}</span>
          </div>
          <div class="progress-container">
            <div class="progress-bar" style="width: ${b.soc ?? 0}%;"></div>
          </div>
          <p class="battery-soc">${b.soc ?? 0}%</p>
        </div>
      </label>
    `).join("");
    attachSelectionLogic();
  }

  // =========================
  // 🔹 Visuel de sélection
  // =========================
  function attachSelectionLogic() {
    document.querySelectorAll(".battery-checkbox").forEach(cb => {
      cb.addEventListener("change", () => {
        cb.closest(".battery-card").classList.toggle("selected", cb.checked);
      });
    });
  }

  // =========================
  // 🔹 Recherche locale
  // =========================
  document.getElementById("searchGlobal").addEventListener("input", e => filterBatteries(e, globalContainer));
  document.getElementById("searchStation").addEventListener("input", e => filterBatteries(e, stationContainer));

  function filterBatteries(e, container) {
    const query = e.target.value.toLowerCase();
    container.querySelectorAll(".battery-card").forEach(card => {
      const mac = card.dataset.mac.toLowerCase();
      card.style.display = mac.includes(query) ? "" : "none";
    });
  }

  // =========================
  // 🔹 Validation (modale)
  // =========================
  const validateBtn = document.getElementById("validateBtn");
  const overlay = document.getElementById("confirmOverlay");
  const modalClose = document.getElementById("modalClose");
  const cancelBtn = document.getElementById("cancelBtn");
  const confirmBtn = document.getElementById("confirmBtn");

  const addList = document.getElementById("addList");
  const removeList = document.getElementById("removeList");
  const addCount = document.getElementById("addCount");
  const removeCount = document.getElementById("removeCount");
  const confirmAgencyName = document.getElementById("confirmAgencyName");
  const confirmAgencyId = document.getElementById("confirmAgencyId");

  validateBtn.addEventListener("click", () => {
    const agencyId = stationSelect.value;
    const agencyName = stationSelect.selectedOptions[0]?.dataset.name || "—";
    const add = [...document.querySelectorAll('input[data-type="add"]:checked')].map(c => c.closest(".battery-card").dataset.mac);
    const remove = [...document.querySelectorAll('input[data-type="remove"]:checked')].map(c => c.closest(".battery-card").dataset.mac);

    if (!agencyId) return simpleToast("Veuillez sélectionner une agence d’abord.");
    if (add.length === 0 && remove.length === 0) return simpleToast("Sélectionnez au moins une batterie.");

    confirmAgencyId.textContent = agencyId;
    confirmAgencyName.textContent = agencyName;
    addList.innerHTML = add.map(mac => `<div class="list-item"><span>${mac}</span><span class="badge">Ajout</span></div>`).join('') || `<div class="list-item"><span>Aucune</span></div>`;
    removeList.innerHTML = remove.map(mac => `<div class="list-item"><span>${mac}</span><span class="badge">Retrait</span></div>`).join('') || `<div class="list-item"><span>Aucune</span></div>`;
    addCount.textContent = add.length;
    removeCount.textContent = remove.length;
    overlay.classList.add("show");
  });

  [modalClose, cancelBtn].forEach(btn => btn.addEventListener("click", () => overlay.classList.remove("show")));

  confirmBtn.addEventListener("click", async () => {
    const agencyId = stationSelect.value;
    const add = [...document.querySelectorAll('input[data-type="add"]:checked')].map(c => c.closest(".battery-card").dataset.mac);
    const remove = [...document.querySelectorAll('input[data-type="remove"]:checked')].map(c => c.closest(".battery-card").dataset.mac);

    try {
     await fetch("/ravitaillement", {
    method: "POST",
    headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content")
    },
    body: JSON.stringify({ station_id: agencyId, add, remove })
    });
     showPopup("success","Ravitaillement effectué avec succès !");
      overlay.classList.remove("show");
      await loadStationBatteries(agencyId);
      await loadGlobalBatteries();
    } catch {
      showPopup("error", e.message || "Errreur lors du ravitaillement");
    }
  });

 
});
</script>
@endsection
