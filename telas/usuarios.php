<?php

require 'proteger.php';
require 'conexao.php';

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
    <button class="menu-btn" id="menuBtn" aria-label="Abrir menu">
      <span></span>
      <span></span>
      <span></span>
    </button>

    <h1>FerroHub</h1>

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


    </nav>

  </aside>

  <div class="overlay" id="overlay"></div>

  <main class="container">
    <h2>Usuários</h2>

    <section class="panel">
      <ul class="user-list">
        <li class="user-card">
          <div class="avatar avatar-placeholder">R</div>
          <div class="user-info">
            <p><strong>Nome:</strong> Rogerio</p>
            <p><strong>E-mail:</strong> rogerioguedes@gmail.com</p>
            <p><strong>Cargo:</strong> Admin</p>
            <p class="status-line">
              <strong>Status:</strong>
              <span class="badge badge-green">Ativo</span>
            </p>
          </div>
        </li>

        <li class="user-card">
          <div class="avatar">
            <img
              src="../assets/usuarios.png"
              alt="Avatar de Vitor" />
          </div>
          <div class="user-info">
            <p><strong>Nome:</strong> Vitor</p>
            <p><strong>E-mail:</strong> vitort@gmail.com</p>
            <p><strong>Cargo:</strong> Usuário comum</p>
            <p class="status-line">
              <strong>Status:</strong>
              <span class="badge badge-red">Inativo</span>
            </p>
          </div>
        </li>

        <li class="user-card">
          <div class="avatar">
            <img
              src="../assets/usuarios.png"
              alt="Avatar de Marcela" />
          </div>
          <div class="user-info">
            <p><strong>Nome:</strong> Marcela</p>
            <p><strong>E-mail:</strong> marcelam@gmail.com</p>
            <p><strong>Cargo:</strong> Operadora</p>
            <p class="status-line">
              <strong>Status:</strong>
              <span class="badge badge-green">Ativa</span>
            </p>
          </div>
        </li>

        <li
          class="user-card add-card"
          tabindex="0"
          role="button"
          aria-label="Adicionar novo usuário">
          <div class="avatar avatar-add">
            <span class="plus">+</span>
          </div>
          <div class="user-info">
            <p><strong>Nome:</strong></p>
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