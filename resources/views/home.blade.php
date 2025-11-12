@extends('layouts.base')

@section('title', 'Accueil - ProxymSwapWeb')

@section('content')
<header class="hero">
    <h1 style="color:#101010;">Bienvenue sur ProxymSwapWeb 👋 </h1>
    <p style="color:#101010;">Bonjour <strong>{{ Auth::user()->nom }} {{ Auth::user()->prenom }}</strong> !</p>
</header>

<main class="home-main">
    <section class="card-grid">
        <!-- Faire un Swap -->
        <a href="{{ route('swap.index') }}" class="home-card">
            <div class="icon">🔁</div>
            <div class="text">
                <h3>Faire un Swap</h3>
                <p>Échanger ou transférer une batterie.</p>
            </div>
        </a>

        <!-- Historique -->
        <a href="{{ route('swap.historique.employe.view', ['employeeId' => auth()->user()->id]) }}" class="home-card">
            <div class="icon">📜</div>
            <div class="text">
                <h3>Historique</h3>
                <p>Voir les swaps précédents.</p>
            </div>
        </a>
         <a href="{{ route('ravitaillement.index') }}" class="home-card">
            <div class="icon">🔁</div>
            <div class="text">
                <h3>Ravittaillement </h3>
                <p>Ravitailler une station .</p>
            </div>
        </a>
         <a href="{{ route('batteries.control') }}" class="home-card">
            <div class="icon">🔁</div>
            <div class="text">
                <h3>Control Batteries </h3>
                <p>Couper et Rallumer les Batteries</p>
            </div>
        </a>

        <!-- Déconnexion -->
        <form method="POST" action="{{ route('logout') }}" class="home-card logout-card">
            @csrf
            <x-dropdown-link :href="route('logout')"
                onclick="event.preventDefault(); this.closest('form').submit();">
                <div class="icon">🚪</div>
                <div class="text">
                    <h3>Déconnexion</h3>
                    <p>Quitter votre session.</p>
                </div>
            </x-dropdown-link>
        </form>
    </section>
</main>

<style>
:root {
    --bg: #101010;
    --text: #F3F3F3;
    --brand: #DCDB32;
    --card: #1B1B1B;
}

/* ==================== STRUCTURE ==================== */
body {
    background: var(--bg);
    color: var(--text);
    margin: 0;
    font-family: 'Nunito', sans-serif;
}

/* ==================== HEADER ==================== */
header.hero {
    background-color: var(--brand);
    color: #101010;
    text-align: center;
    padding: 25px 10px;
    border-radius: 0;
    margin-bottom: 20px;
    width: 100%;
    box-shadow: 0 3px 8px rgba(0,0,0,0.3);
}

header.hero h1 {
    margin: 0;
    font-size: 1.6rem;
    font-weight: 700;
}

header.hero p {
    margin-top: 8px;
    font-size: 1rem;
    font-weight: 600;
}

/* ==================== CONTENU PRINCIPAL ==================== */
.home-main {
    padding: 0 20px 40px;
}

/* ==================== GRILLE ==================== */
.card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 16px;
}

/* ==================== CARDS ==================== */
.home-card {
    background: var(--card);
    border-radius: 16px;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.3);
    padding: 20px;
    text-align: center;
    color: var(--text);
    text-decoration: none;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
    border: 1px solid #333;
    position: relative;
    overflow: hidden;
}

/* Ripple Android */
.home-card::after {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: rgba(220, 219, 50, 0.4);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: width 0.4s ease-out, height 0.4s ease-out, opacity 0.8s;
    pointer-events: none;
}
.home-card:active::after {
    width: 300%;
    height: 300%;
    opacity: 0;
    transition: 0s;
}

/* Hover */
.home-card:hover {
    transform: translateY(-3px);
    border-color: var(--brand);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.5);
}

/* Icones & textes */
.icon {
    font-size: 2rem;
    margin-bottom: 10px;
    color: var(--brand);
}
.text h3 {
    margin: 0;
    font-size: 1.1rem;
    color: var(--brand);
}
.text p {
    font-size: 0.85rem;
    color: #bbb;
    margin-top: 4px;
}

/* ==================== DÉCONNEXION ==================== */
.logout-card:hover {
    background: #f44336;
    border-color: #f44336;
    transform: translateY(-3px);
}

/* ==================== RESPONSIVE ==================== */
@media (max-width: 480px) {
    .home-card {
        padding: 16px;
    }
    .home-card .icon {
        font-size: 1.8rem;
    }
    .text h3 {
        font-size: 1rem;
    }
    .text p {
        font-size: 0.8rem;
    }
}
</style>
@endsection
