<?php

require_once __DIR__ . '/proteger.php';
require_once __DIR__ . '/conexao.php';

if (!isset($conexao) || $conexao->connect_error) {
    die("Erro: Conexão com o banco não disponível. Verifique o ficheiro conexao.php.");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Id do usuário logado (ajuste a chave se na sua sessão for outra)
$idLogado = $_SESSION['id_usuario'] ?? null;

$mensagemSucesso = '';
$mensagemErro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];

    // ---------- ALTERAR SENHA ----------
    if ($acao === 'alterar_senha') {
        $idUsuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
        $novaSenha = $_POST['nova_senha'] ?? '';

        if ($idUsuario && !empty(trim($novaSenha))) {
            $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
            $stmt = $conexao->prepare("UPDATE usuario SET senha = ? WHERE id_usuario = ?");
            $stmt->bind_param('si', $senhaHash, $idUsuario);
            $mensagemSucesso = $stmt->execute() ? "Senha alterada com sucesso!" : "Erro ao alterar a senha do usuário.";
            $stmt->close();
        } else {
            $mensagemErro = "Preencha todos os campos corretamente.";
        }
    }

    // ---------- ADICIONAR USUÁRIO ----------
    if ($acao === 'adicionar_usuario') {
        $login  = trim($_POST['login'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $cargo  = trim($_POST['cargo'] ?? '');
        $status = ($_POST['status'] ?? '') === 'inativo' ? 'inativo' : 'ativo';
        $senha  = $_POST['senha'] ?? '';

        if ($login === '' || $cargo === '' || trim($senha) === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mensagemErro = "Preencha todos os campos corretamente.";
        } else {
            // Verifica duplicidade de login/e-mail
            $stmt = $conexao->prepare("SELECT id_usuario FROM usuario WHERE login = ? OR email = ? LIMIT 1");
            $stmt->bind_param('ss', $login, $email);
            $stmt->execute();
            $stmt->store_result();
            $existe = $stmt->num_rows > 0;
            $stmt->close();

            if ($existe) {
                $mensagemErro = "Já existe um usuário com esse login ou e-mail.";
            } else {
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $conexao->prepare("INSERT INTO usuario (login, email, senha, cargo, status) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('sssss', $login, $email, $senhaHash, $cargo, $status);
                $mensagemSucesso = $stmt->execute() ? "Usuário adicionado com sucesso!" : "Erro ao adicionar o usuário.";
                $stmt->close();
            }
        }
    }

    // ---------- EXCLUIR USUÁRIO ----------
    if ($acao === 'excluir_usuario') {
        $idUsuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);

        if (!$idUsuario) {
            $mensagemErro = "Usuário inválido.";
        } elseif ($idLogado !== null && (int)$idLogado === $idUsuario) {
            $mensagemErro = "Você não pode excluir o seu próprio usuário.";
        } else {
            $stmt = $conexao->prepare("DELETE FROM usuario WHERE id_usuario = ?");
            $stmt->bind_param('i', $idUsuario);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $mensagemSucesso = "Usuário excluído com sucesso!";
            } else {
                $mensagemErro = "Erro ao excluir o usuário.";
            }
            $stmt->close();
        }
    }
}

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
    <button class="menu-btn" id="menuBtn" type="button" aria-label="Abrir menu" aria-controls="sidebar" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
    <a href="home.php"><h1>FerroHub</h1></a>
    <a class="leituras-btn" href="leituras.php">
      <img src="../assets/sino.png" alt="">
      <span>Leituras</span>
    </a>
    <a class="logout-btn" href="logout.php">Sair</a>
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



      <a
        href="dashboard.php"
        class="side-link">

        <img
          src="../assets/dashboard.png"
          alt="">

        <span>
          Dashboard
        </span>

      </a>


    </nav>

  </aside>

  <div class="overlay" id="overlay"></div>

  <main class="container">
    <h2>Usuários</h2>

    <?php if ($mensagemSucesso): ?>
      <div class="alert alert-ok"><?= htmlspecialchars($mensagemSucesso) ?></div>
    <?php endif; ?>
    <?php if ($mensagemErro): ?>
      <div class="alert alert-erro"><?= htmlspecialchars($mensagemErro) ?></div>
    <?php endif; ?>

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

            <?php if ($idLogado === null || (int)$idLogado !== (int)$usuario['id_usuario']): ?>
              <form method="POST" class="delete-form"
                    onsubmit="return confirm('Excluir o usuário \'<?= htmlspecialchars($usuario['login'], ENT_QUOTES) ?>\'? Esta ação não pode ser desfeita.');">
                <input type="hidden" name="acao" value="excluir_usuario">
                <input type="hidden" name="id_usuario" value="<?= (int)$usuario['id_usuario'] ?>">
                <button type="submit" class="btn-excluir">Excluir</button>
              </form>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>

        <li class="user-card add-card" id="addCard" tabindex="0" role="button" aria-label="Adicionar novo usuário">
          <div class="avatar avatar-add"><span class="plus">+</span></div>
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

  <!-- Modal de novo usuário -->
  <div class="modal-overlay" id="modalAdd" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitulo">
      <h3 id="modalTitulo">Novo usuário</h3>
      <form method="POST">
        <input type="hidden" name="acao" value="adicionar_usuario">

        <label for="login">Login</label>
        <input type="text" id="login" name="login" required maxlength="50">

        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" required>

        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" required>

        <label for="cargo">Cargo</label>
        <input type="text" id="cargo" name="cargo" list="cargos" required>
        <datalist id="cargos">
          <option value="administrador">
          <option value="usuario comum">
        </datalist>

        <label for="status">Status</label>
        <select id="status" name="status">
          <option value="ativo">Ativo</option>
          <option value="inativo">Inativo</option>
        </select>

        <div class="modal-actions">
          <button type="button" class="btn-cancelar" id="btnCancelar">Cancelar</button>
          <button type="submit" class="btn-salvar">Adicionar</button>
        </div>
      </form>
    </div>
  </div>

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

    // Modal de adicionar usuário
    const addCard = document.getElementById("addCard");
    const modalAdd = document.getElementById("modalAdd");
    const btnCancelar = document.getElementById("btnCancelar");

    const abrirModal = () => {
      modalAdd.classList.add("active");
      modalAdd.setAttribute("aria-hidden", "false");
      document.getElementById("login").focus();
    };
    const fecharModal = () => {
      modalAdd.classList.remove("active");
      modalAdd.setAttribute("aria-hidden", "true");
    };

    addCard.addEventListener("click", abrirModal);
    addCard.addEventListener("keydown", (e) => {
      if (e.key === "Enter" || e.key === " ") { e.preventDefault(); abrirModal(); }
    });
    btnCancelar.addEventListener("click", fecharModal);
    modalAdd.addEventListener("click", (e) => { if (e.target === modalAdd) fecharModal(); });
    document.addEventListener("keydown", (e) => { if (e.key === "Escape") fecharModal(); });
  </script>
</body>

</html>