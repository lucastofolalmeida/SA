<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

require 'conexao.php';

if (isset($_SESSION['id_usuario'])) {
  header('Location: home.php');
  exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $login = trim($_POST['login'] ?? '');
  $senha = $_POST['senha'] ?? '';

  if ($login === '') {
    $erro = 'Preencha o nome.';
  } elseif ($senha === '') {
    $erro = 'Preencha a senha.';
  } else {
    $stmt = $conexao->prepare(
      'SELECT id_usuario, login, senha FROM usuario WHERE login = ? LIMIT 1'
    );

    $stmt->bind_param('s', $login);
    $stmt->execute();

    $usuario = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if ($usuario && password_verify($senha, $usuario['senha'])) {
      session_regenerate_id(true);

      $_SESSION['id_usuario'] = (int) $usuario['id_usuario'];
      $_SESSION['usuario_login'] = $usuario['login'];

      header('Location: home.php');
      exit;
    }

    $erro = 'Nome ou senha inválidos.';
  }
}

?>
<!doctype html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login</title>

  <link rel="stylesheet" href="../styles/Login.css" />

  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
</head>

<body>
  <main class="container">
    <h1>Login:</h1>

    <form id="form-cadastro" method="post">
      <div class="input-box">
        <i class="fa-regular fa-user"></i>
        <input
          type="text"
          id="login"
          name="login"
          placeholder="Enter name"
          required />
      </div>

      <div class="input-box senha-box">
        <i class="fa-solid fa-lock"></i>

        <input
          type="password"
          id="senha"
          name="senha"
          placeholder="Password"
          required />

        <i class="fa-regular fa-eye olho" id="olhoSenha"></i>
      </div>

      <button type="submit">Login</button>

      <?php if ($erro !== ''): ?>
        <p id="mensagem"><?= htmlspecialchars($erro) ?></p>
      <?php endif; ?>

      <p id="validacao"></p>
    </form>

    <div class="login">
      <p>
        Não tem uma conta?
        <a href="sigin.php"> <em>create your account</em></a>
      </p>
    </div>
  </main>

  <script>
    const form = document.getElementById("form-cadastro");
    const mensagem = document.getElementById("mensagem");
    const validacao = document.getElementById("validacao");

    const olhoSenha = document.getElementById("olhoSenha");
    const senha = document.getElementById("senha");

    olhoSenha.addEventListener("click", () => {
      senha.type = senha.type === "password" ? "text" : "password";
    });

    form.addEventListener("submit", function(event) {
      const login = document.getElementById("login").value.trim();
      const senhaValor = senha.value;

      mensagem.textContent = "";

      if (login === "") {
        event.preventDefault();
        mensagem.textContent = "Preencha o l.";
        return;
      }
    });
  </script>
</body>

</html>