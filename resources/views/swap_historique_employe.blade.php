@extends('layouts.base')

@section('title', 'Historique des Swaps - ProxymSwapWeb')

@section('content')
<header class="swap-hero">
  <a href="{{ route('home') }}" class="back-btn">⟵</a>
  <button id="refreshBtn" title="Rafraîchir">⟳</button>
  <h1>📜 Historique des Swaps</h1>
  <p>Suivi des swaps effectués par {{ $employee->nom }} {{ $employee->prenom }}</p>
</header>

<main class="swap-main">
  <!-- Filtres -->
  <section class="filters">
    <h3>🕒 Filtres rapides</h3>
    <div class="filter-buttons">
      <button class="filter-btn" data-period="today">Aujourd’hui</button>
      <button class="filter-btn" data-period="yesterday">Hier</button>
      <button class="filter-btn" data-period="week">Cette semaine</button>
      <button class="filter-btn" data-period="month">Ce mois</button>
      <button class="filter-btn" data-period="year">Cette année</button>
      <button class="filter-btn" data-period="specific">Date spécifique</button>
      <button class="filter-btn" data-period="custom">Plage de dates</button>
    </div>

    <!-- Sélection date spécifique -->
    <div id="specificDateContainer" style="display:none; margin-top:10px;">
      <input type="date" id="specificDate" class="input-text" />
    </div>

    <!-- Sélection plage -->
    <div id="customDateContainer" style="display:none; margin-top:10px;">
      <input type="date" id="startDate" class="input-text" /> 
      <span style="color:#bbb;">→</span>
      <input type="date" id="endDate" class="input-text" />
    </div>

    <!-- Sélection Swapper -->
    <div class="form-group" style="margin-top:15px;">
      <label for="swapperSelect">Agent de Swap :</label>
      <select id="swapperSelect" class="input-select">
        <option value="all">Tous les swappers</option>
        @foreach ($swappers as $s)
          <option value="{{ $s->id }}">{{ $s->nom }} {{ $s->prenom }}</option>
        @endforeach
      </select>
    </div>
  </section>

  <!-- Résumé -->
  <section class="summary-card">
    <p>🔢 <strong id="swapCount">0</strong> swaps trouvés</p>
    <p>💰 <strong id="totalPrice">0 FCFA</strong> au total</p>
  </section>

  <!-- Liste des swaps -->
  <section id="swapList">
    <p style="text-align:center;color:#888;">Chargement des données...</p>
  </section>
</main>

<style>
:root {
  --bg: #101010;
  --text: #f3f3f3;
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
  background: #1b1b1b; color: var(--brand);
  border: none; border-radius: 10px;
  padding: 8px 12px; font-size: 1.2rem; cursor: pointer;
}
.back-btn { left: 12px; }
#refreshBtn { right: 12px; }

.swap-main { padding: 25px 20px 50px; }
.filters h3 { color: var(--brand); margin-bottom: 10px; }
.filter-buttons {
  display: flex; flex-wrap: wrap; gap: 8px;
}
.filter-btn {
  background: var(--card); border: 1px solid var(--border);
  color: var(--text); padding: 8px 14px; border-radius: 8px; cursor: pointer;
}
.filter-btn.active { background: var(--brand); color: #101010; font-weight: 700; }

.input-text, .input-select {
  width: 100%; background: var(--card); color: var(--text);
  border: 1px solid var(--border); border-radius: 10px;
  padding: 10px 14px; font-size: 0.95rem;
}

.summary-card {
  background: var(--card);
  border-radius: 10px;
  padding: 12px 16px;
  margin: 20px 0;
  display: flex; justify-content: space-between;
  border: 1px solid var(--border);
}
.swap-band {
  background: var(--card);
  border-radius: 14px;
  padding: 14px;
  margin-bottom: 12px;
  border: 1px solid var(--border);
}
.swap-band p { margin: 4px 0; font-size: 0.9rem; }
.progress-container {
  background: #2a2a2a; border-radius: 10px;
  height: 8px; overflow: hidden; margin-top: 4px;
}
.progress-bar { height: 100%; border-radius: 10px; transition: width 0.3s; }
</style>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const employeeId = {{ $employee->id }};
  const swapList = document.getElementById("swapList");
  const swapCount = document.getElementById("swapCount");
  const totalPrice = document.getElementById("totalPrice");
  const filterBtns = document.querySelectorAll(".filter-btn");
  const swapperSelect = document.getElementById("swapperSelect");

  let currentPeriod = "today";

  function setActive(btn) {
    filterBtns.forEach(b => b.classList.remove("active"));
    btn.classList.add("active");
  }

  async function loadSwaps(period, extras = {}) {
    try {
      swapList.innerHTML = "<p style='text-align:center;color:#888;'>Chargement...</p>";
      const swapperId = swapperSelect.value || 'all';
      const params = new URLSearchParams({ periode: period, swapper_id: swapperId });

      if (period === 'specific' && extras.specific_date) params.append('specific_date', extras.specific_date);
      if (period === 'custom' && extras.start_date && extras.end_date) {
        params.append('start_date', extras.start_date);
        params.append('end_date', extras.end_date);
      }

      const res = await fetch(`/swap/historique/employe/data/${employeeId}?` + params.toString());
      const j = await res.json();

      if (!res.ok) throw new Error(j.message || "Erreur serveur");

      swapCount.textContent = j.count;
      totalPrice.textContent = `${j.total.toLocaleString()} FCFA`;

      if (!j.swaps.length) {
        swapList.innerHTML = "<p style='text-align:center;color:#777;'>Aucun swap trouvé pour cette période.</p>";
        return;
      }

      swapList.innerHTML = "";
      j.swaps.forEach(s => {
        const colorIn  = s.soc_in >= 90 ? "#4caf50" : s.soc_in >= 60 ? "#ff9800" : "#f44336";
        const colorOut = s.soc_out >= 90 ? "#4caf50" : s.soc_out >= 60 ? "#ff9800" : "#f44336";
        const div = document.createElement("div");
        div.className = "swap-band";
        div.innerHTML = `
          <p>👤 <strong>${s.chauffeur}</strong> (${s.swapper})</p>
          <p>🔋 Sortie: <strong>${s.battery_out}</strong> (${s.soc_out}%)</p>
          <div class="progress-container"><div class="progress-bar" style="width:${s.soc_out}%;background:${colorOut}"></div></div>
          <p>🔋 Entrée: <strong>${s.battery_in}</strong> (${s.soc_in}%)</p>
          <div class="progress-container"><div class="progress-bar" style="width:${s.soc_in}%;background:${colorIn}"></div></div>
          <p>💰 ${s.prix.toLocaleString()} FCFA — 🕒 ${s.date}</p>
        `;
        swapList.appendChild(div);
      });
    } catch (e) {
      console.error(e);
      swapList.innerHTML = "<p style='color:#f44336;text-align:center;'>Erreur de chargement</p>";
    }
  }

  // Par défaut → Aujourd’hui
  loadSwaps("today");
  document.querySelector('.filter-btn[data-period="today"]').classList.add("active");

  filterBtns.forEach(btn => {
    btn.addEventListener("click", () => {
      const period = btn.dataset.period;
      setActive(btn);
      currentPeriod = period;

      document.getElementById("specificDateContainer").style.display = (period === "specific") ? "block" : "none";
      document.getElementById("customDateContainer").style.display = (period === "custom") ? "block" : "none";

      if (["today", "yesterday", "week", "month", "year"].includes(period)) {
        loadSwaps(period);
      }
    });
  });

  document.getElementById("specificDate").addEventListener("change", e => {
    if (currentPeriod === "specific") loadSwaps("specific", { specific_date: e.target.value });
  });

  ["startDate", "endDate"].forEach(id => {
    document.getElementById(id).addEventListener("change", () => {
      const start = document.getElementById("startDate").value;
      const end   = document.getElementById("endDate").value;
      if (start && end && currentPeriod === "custom") loadSwaps("custom", { start_date: start, end_date: end });
    });
  });

  swapperSelect.addEventListener("change", () => {
    loadSwaps(currentPeriod);
  });

  document.getElementById("refreshBtn").onclick = () => loadSwaps(currentPeriod);
});
</script>
@endsection
