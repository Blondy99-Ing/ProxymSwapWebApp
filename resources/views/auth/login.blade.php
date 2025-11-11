<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Connexion - ProxymSwapWeb</title>

  <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
  <meta name="theme-color" content="#DCDB32">

  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --bg: #101010;
      --text: #F3F3F3;
      --brand: #DCDB32;
      --card-bg: #1B1B1B;
      --border: #2a2a2a;
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      font-family: 'Nunito', sans-serif;
      background: var(--bg);
      color: var(--text);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
    }

    .login-container {
      width: 100%;
      max-width: 420px;
      background: var(--card-bg);
      border-radius: 18px;
      padding: 40px 30px 50px;
      box-shadow: 0 0 25px rgba(0,0,0,0.6);
      text-align: center;
      border: 1px solid var(--border);
    }

    .login-logo {
      width: 180px;
      height: auto;
      margin-bottom: 20px;
      filter: drop-shadow(0 0 6px rgba(220,219,50,0.5));
    }

    h1 {
      color: var(--brand);
      font-size: 1.8rem;
      margin-bottom: 5px;
    }

    p.subtitle {
      color: #aaa;
      font-size: 0.95rem;
      margin-bottom: 25px;
    }

    label {
      text-align: left;
      display: block;
      font-weight: 600;
      font-size: 0.9rem;
      color: var(--brand);
      margin-bottom: 6px;
    }

    input[type="email"],
    input[type="password"] {
      width: 100%;
      background: #161616;
      color: var(--text);
      padding: 12px 14px;
      margin-bottom: 18px;
      border: 1px solid var(--border);
      border-radius: 10px;
      outline: none;
      transition: 0.3s;
    }

    input:focus {
      border-color: var(--brand);
      box-shadow: 0 0 6px rgba(220,219,50,0.3);
    }

    .remember {
      display: flex;
      align-items: center;
      justify-content: start;
      gap: 8px;
      margin-bottom: 22px;
      font-size: 0.9rem;
    }

    .remember input {
      accent-color: var(--brand);
    }

    .login-btn {
      width: 100%;
      background-color: var(--brand);
      color: #101010;
      font-weight: 700;
      padding: 12px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-size: 1rem;
      transition: all 0.25s ease;
    }

    .login-btn:hover {
      background-color: #d7d639;
      transform: translateY(-1px);
    }

    .error-message {
      background-color: #ff4747;
      color: #fff;
      padding: 10px;
      border-radius: 8px;
      margin-bottom: 15px;
      font-size: 0.9rem;
    }

    .login-footer {
      text-align: center;
      margin-top: 18px;
      color: #aaa;
      font-size: 0.9rem;
    }

    .login-footer a {
      color: var(--brand);
      text-decoration: none;
      font-weight: 600;
    }

    .login-footer a:hover {
      text-decoration: underline;
    }

    /* Animation douce */
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .login-container { animation: fadeIn 0.6s ease-out; }
  </style>
</head>

<body>

  <div class="login-container">
    <img src="{{ asset('images/icons/logo.png') }}" alt="ProxymSwap Logo" class="login-logo">
    <h1>ProxymSwap</h1>
    <p class="subtitle">Connectez-vous à votre espace employé</p>

    @if ($errors->any())
      <div class="error-message">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
      @csrf

      <label for="email">Adresse e-mail</label>
      <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>

      <label for="password">Mot de passe</label>
      <input id="password" type="password" name="password" required>

      <div class="remember">
        <input id="remember_me" type="checkbox" name="remember">
        <label for="remember_me">Se souvenir de moi</label>
      </div>

      <button type="submit" class="login-btn">Se connecter</button>

      <div class="login-footer">
        @if (Route::has('password.request'))
          <p><a href="{{ route('password.request') }}">Mot de passe oublié ?</a></p>
        @endif
        @if (Route::has('register'))
          <p>Pas encore de compte ? <a href="{{ route('register') }}">Créer un compte</a></p>
        @endif
      </div>
    </form>
  </div>

</body>
</html>
