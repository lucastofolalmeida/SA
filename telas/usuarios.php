<?php

require_once __DIR__ . '/proteger.php';
require_once __DIR__ . '/conexao.php';

if (!isset($conexao) || $conexao->connect_error) {
    die("Erro: Conexão com o banco não disponível. Verifique o ficheiro conexao.php.");
}

$mensagemSucesso = '';
$mensagemErro = '';

// Lógica para processar a alteração de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'alterar_senha') {
    $idUsuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
    $novaSenha = $_POST['nova_senha'] ?? '';

    if ($idUsuario && !empty(trim($novaSenha))) {
        // Criptografa a senha antes de guardar no banco
        $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);

        $stmtUpdate = $conexao->prepare("UPDATE usuario SET senha = ? WHERE id_usuario = ?");
        $stmtUpdate->bind_param('si', $senhaHash, $idUsuario);

        if ($stmtUpdate->execute()) {
            $mensagemSucesso = "Senha alterada com sucesso!";
        } else {
            $mensagemErro = "Erro ao alterar a senha do usuário.";
        }
        $stmtUpdate->close();
    } else {
        $mensagemErro = "Preencha todos os campos corretamente.";
    }
}

// Não é necessário buscar a senha para listar os usuários
$resUsuarios = $conexao->query("SELECT id_usuario, login, email, cargo, status FROM usuario");
$usuarios = $resUsuarios ? $resUsuarios->fetch_all(MYSQLI_ASSOC) : [];

?>

<!doctype html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FerroHub - Usuários</title>
  <link rel="stylesheet" href="../styles/usuarios.css?v=<?= filemtime(__DIR__ . '/../styles/usuarios.css') ?>">
</head>

<body>
   <header class="topbar">
        <button
            class="menu-btn"
            id="menuBtn"
            type="button"
            aria-label="Abrir menu"
            aria-controls="sidebar"
            aria-expanded="false">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <a href="home.php">
            <h1>FerroHub</h1>
        </a>
        <a class="leituras-btn" href="leituras.php">
            <img src="../assets/sino.png" alt="">
            <span>Leituras</span>
        </a>
        <a
            class="logout-btn"
            href="logout.php">
            Sair
        </a>
    </header>
  <aside
    class="sidebar"
    id="sidebar">
    <nav>
      <a
        href="locomotivas.php"
        class="side-link">
        <img
          src="../assets/locomotiva.png"
          alt="">
        <span>
          Locomotivas
        </span>
      </a>
      <a
        href="relatorios.php"
        class="side-link">
        <img
          src="../assets/relatorio.webp"
          alt="">
        <span>
          Relatórios
        </span>
      </a>
      <a
        href="sensores.php"
        class="side-link">
        <img
          src="../assets/sensores.webp"
          alt="">
        <span>
          Sensores
        </span>
      </a>
      <a
        href="rotas.php"
        class="side-link">
        <img
          src="../assets/rotas.png"
          alt="">
        <span>
          Rotas
        </span>
      </a>
      <a
        href="usuarios.php"
        class="side-link">
        <img
          src="../assets/usuarios.png"
          alt="">
        <span>
          Usuários
        </span>
      </a>
    </nav>
  </aside>

  <div class="overlay" id="overlay"></div>
  <main class="container">
    <h2>Usuários</h2>
    <section class="panel">
      <ul class="user-list">
        <?php foreach ($usuarios as $usuario): ?>
          <li class="user-card">
            <div class="avatar avatar-placeholder"><?= strtoupper(substr($usuario['login'], 0, 1)) ?></div>
            <div class="user-info">
              <p><strong>Login:</strong> <?= htmlspecialchars($usuario['login']) ?></p>
              <p><strong>E-mail:</strong> <?= htmlspecialchars($usuario['email']) ?></p>
              <p><strong>Cargo:</strong> <?= htmlspecialchars($usuario['cargo']) ?></p>
              <p class="status-line">
                <strong>Status:</strong>
                <?php if ($usuario['status'] === 'ativo'): ?>
                  <span class="badge badge-green">Ativo</span>
                <?php else: ?>
                  <span class="badge badge-red">Inativo</span>
                <?php endif; ?>
              </p>
            </div>
          </li>
        <?php endforeach; ?>
        <li
          class="user-card add-card"
          tabindex="0"
          role="button"
          aria-label="Adicionar novo usuário">
          <div class="avatar avatar-add">
            <span class="plus">+</span>
          </div>
          <div class="user-info">
            <p><strong>Login:</strong></p>
            <p><strong>E-mail:</strong></p>
            <p><strong>Cargo:</strong></p>
            <p><strong>Status:</strong></p>
          </div>
        </li>
      </ul>
    </section>
  </main>

  <script>
    const menuBtn = document.getElementById("menuBtn");
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("overlay");

    menuBtn.addEventListener("click", () => {
      menuBtn.classList.toggle("active");
      sidebar.classList.toggle("active");
      overlay.classList.toggle("active");
    });

    overlay.addEventListener("click", () => {
      sidebar.classList.remove("active");
      menuBtn.classList.remove("active");
      overlay.classList.remove("active");
    });
  </script>
</body>

</html>