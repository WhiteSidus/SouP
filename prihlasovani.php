<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Přihlašování</title>
</head>
<body>
    <section class="screen" id="login" data-index="3">
        <div class="back-row">
          <button class="back-btn" data-screen-target="home" aria-label="Zpět">←</button>
          <p class="screen-title">Přihlášení:</p>
        </div>

        <form id="loginForm" class="form-stack">
          <div>
            <label class="field-label" for="loginEmail">E-mail:</label>
            <input class="input" id="loginEmail" name="email" type="email" required>
          </div>
          <div>
            <label class="field-label" for="loginPassword">Heslo:</label>
            <input class="input" id="loginPassword" name="password" type="password" required>
          </div>
          <button class="primary-btn auth-submit" type="submit">Přihlásit</button>
          <div class="auth-links">
            <span>Nemáte přihlášení?</span>
            <button class="link-btn" type="button" data-screen-target="register">Registrovat</button>
          </div>
          <div id="loginNotice" class="notice"></div>
        </form>
      </section>
</body>
</html>