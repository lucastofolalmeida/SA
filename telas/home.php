<?php
require 'proteger.php';
require 'conexao.php';
?>

<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>FerroHub</title>

    <link rel="stylesheet" href="../styles/home.css?v=<?= filemtime(__DIR__ . '/../styles/home.css') ?>"/>
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
      <h2>Bem vindo à Central de monitoramento:</h2>

      <section class="cards">
        <a href="sensores.php" class="card">
          <p class="alarme ativo sensor">⚠</p>
          <img src="../assets/sensores.webp" alt="" />

          <span>Sensores</span>
        </a>

        <a href="locomotivas.php" class="card">
          <p class="alarme ativo">⚠</p>
          <img src="../assets/locomotiva.png" alt="" />

          <span>Locomotivas</span>
        </a>

        <a href="relatorios.php" class="card">
          <img src="../assets/relatorio.webp" alt="" />

          <span>Relatórios</span>
        </a>

        <a href="rotas.php" class="card">
          <img src="../assets/Rotas.png" alt="" />

          <span>Rotas</span>
        </a>

        <a href="usuarios.php" class="card">
          <img src="../assets/usuarios.png" alt="" />

          <span>Usuários</span>
        </a>
      </section>

      <div class="logo-area">
        <img src="../assets/logo.webp" alt="Logo FerroHub" />
      </div>
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
