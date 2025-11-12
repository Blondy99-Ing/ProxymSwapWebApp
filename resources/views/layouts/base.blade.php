<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <title>@yield('title', 'ProxymSwapWeb')</title>

  {{-- Manifest + thème --}}
  <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
  <meta name="theme-color" content="#DCDB32">

  {{-- Fonts / styles globaux --}}
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

  <style>
    :root{ --bg:#F3F3F3; --text:#101010; --brand:#DCDB32; }
    *{ box-sizing:border-box }
    body{ margin:0; font-family:'Nunito',sans-serif; background:var(--bg); color:var(--text); }
    .container{ padding:0px; max-width:1200px; margin:0 auto; }
    header.hero{ background:var(--brand); padding:20px; text-align:center; border-radius:8px; }
    /* Bandeau “installer l’app” */
    #installBanner{
      position:fixed; left:0; right:0; bottom:0; display:none;
      background:#101010; color:#fff; padding:12px 16px; gap:12px; align-items:center; justify-content:space-between;
    }
    #installBanner button{
      background:#DCDB32; color:#101010; border:none; border-radius:8px; padding:8px 12px; font-weight:700;
    }
  </style>
  
<style>
    :root {
        --brand: #DCDB32;
        --text: #101010;
        --bg: #F3F3F3;
    }

    header.hero {
        background-color: var(--brand);
        padding: 20px;
        text-align: center;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    header.hero h1 {
        margin: 0;
        color: #101010;
        font-size: 2rem;
    }

    header.hero p {
        margin-top: 8px;
        color: #101010;
        font-size: 1.2rem;
    }

    .home-main {
        margin-top: 0px;
    }

    .card-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 16px;
    }

    .home-card {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        padding: 20px;
        text-align: center;
        color: var(--text);
        text-decoration: none;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .home-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
    }

    .home-card .icon {
        font-size: 2rem;
        margin-bottom: 10px;
    }

    .home-card h3 {
        margin: 0;
        font-size: 1rem;
        color: var(--text);
    }

    .home-card p {
        font-size: 0.85rem;
        color: #666;
        margin-top: 4px;
    }

    .logout-card button {
        width: 100%;
        background: none;
        border: none;
        color: inherit;
        padding: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }



    .home-card {
    position: relative;
    overflow: hidden; /* nécessaire pour le ripple */
}

.home-card::after {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: rgba(220, 219, 50, 0.4); /* couleur jaune semi-transparente */
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



.back-btn, #refreshBtn {
  position: absolute; top: 12px;
  background: var(--brand); color: var(--bg);
  padding: 1px 2px; font-size: 1.2rem; cursor: pointer;
  border:none; border-radius: 10px; 
  border-color: #1010101a;
  z-index: 1000;
  font-size: 2rem;
  font-family: bold;
  text-decoration:none;
}
.back-btn { left: 12px; }
#refreshBtn { right: 12px; }


/* === TOAST ANDROID-LIKE === */

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


    /* Adaptation mobile */
    @media (max-width: 480px) {
        .home-card {
            padding: 16px;
        }
        .home-card .icon {
            font-size: 1.8rem;
        }
        .home-card h3 {
            font-size: 0.95rem;
        }
        .home-card p {
            font-size: 0.8rem;
        }
    }
</style
</head>
<body>
<button onclick="window.location.reload();" 
  style="position: fixed; top: 10px; right: 10px; background: #DCDB32; 
         color:#101010; border:none; border-radius:50%; width:45px; height:45px;
         font-weight:bold; font-size:18px; box-shadow:0 2px 6px rgba(0,0,0,0.2);">
  ⟳
</button>

  <div id="app" class="container">
   


    @yield('content')
    
  </div>

  {{-- ✅ POPUP DE SUCCÈS / ÉCHEC --}}
<div id="popup" class="popup hidden">
  <div id="popupIcon" class="popup-icon"></div>
  <p id="popupMsg" class="popup-msg"></p>
</div>

  {{-- Bandeau Install PWA --}}
  <div id="installBanner">
    <span>Installer <strong>ProxymSwap</strong> sur votre appareil ?</span>
    <div>
      <button id="installBtn">Installer</button>
      <button id="installClose" style="margin-left:8px;background:#fff;color:#101010">Plus tard</button>
    </div>
  </div>
  

  <script>
    // Enregistrement du Service Worker
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(console.error);
      });
    }

    // Gestion du prompt d'installation (A2HS)
    let deferredPrompt = null;
    const banner = document.getElementById('installBanner');
    const btnInstall = document.getElementById('installBtn');
    const btnClose = document.getElementById('installClose');

    window.addEventListener('beforeinstallprompt', (e) => {
      e.preventDefault();           // on empêche le prompt auto
      deferredPrompt = e;           // on garde l’événement
      banner.style.display = 'flex';// on montre le bandeau
    });

    btnInstall?.addEventListener('click', async () => {
      if (!deferredPrompt) return;
      deferredPrompt.prompt();                      // affiche le prompt d’installation
      const { outcome } = await deferredPrompt.userChoice;
      // outcome: 'accepted' | 'dismissed'
      banner.style.display = 'none';
      deferredPrompt = null;
      console.log('Install outcome:', outcome);
    });

    btnClose?.addEventListener('click', () => {
      banner.style.display = 'none';
    });

    // Optionnel: cacher le bandeau si déjà “standalone”
    if (window.matchMedia('(display-mode: standalone)').matches) {
      document.getElementById('installBanner')?.remove();
    }
  </script>

  <script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.addEventListener('controllerchange', () => {
    // Quand un nouveau SW prend le relais → rechargement
    window.location.reload();
  });

  navigator.serviceWorker.getRegistration().then(reg => {
    if (reg && reg.waiting) {
      if (confirm("Nouvelle version disponible. Recharger maintenant ?")) {
        reg.waiting.postMessage({ type: 'SKIP_WAITING' });
      }
    }

    reg && reg.addEventListener('updatefound', () => {
      const newWorker = reg.installing;
      newWorker.addEventListener('statechange', () => {
        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
          if (confirm("Nouvelle version disponible. Recharger ?")) {
            newWorker.postMessage({ type: 'SKIP_WAITING' });
          }
        }
      });
    });
  });
}
</script>



<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js').then(reg => {
    // 🔁 Mise à jour automatique quand une nouvelle version est disponible
    reg.onupdatefound = () => {
      const newWorker = reg.installing;
      newWorker.onstatechange = () => {
        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
          // 🔔 Notification visuelle optionnelle
          console.log("🔁 Nouvelle version détectée, mise à jour en cours...");
          newWorker.postMessage({ action: 'skipWaiting' });
          location.reload();
        }
      };
    };
  });
}
</script>

<Script>
function showPopup(type, message) {
    popup.className = `popup ${type} show`;
    popupIcon.innerHTML = type === "success" ? "✅" : "❌";
    popupMsg.textContent = message;
    setTimeout(() => popup.classList.remove("show"), 2500);
  }
</Script>


</body>
</html>
