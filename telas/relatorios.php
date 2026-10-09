<?php

require 'proteger.php';
require 'conexao.php';

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios</title>
   <link rel="stylesheet" href="../styles/relatorios.css?v=<?= filemtime(__DIR__ . '/../styles/relatorios.css') ?>">
</head>
<body>
    <div class="overlay" id="overlay"></div>

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

    <main class="container">
      <h2>Status da locomotivas:</h2>
      <h3>Locomotiva - Curitiba Express</h3>

      <section class="cards-grid">
        <div class="card"><a><strong>Motor Diesel:</strong></a><h4 class="id-status">ÓTIMA</h4></div>
        <div class="card"><a><strong>Pressão de Óleo:</strong></a><h4 class="id-status">65 PSI (Normal)</h4></div>
        <div class="card"><a><strong>RPM do Motor:</strong></a><h4 class="id-status">1450 RPM</h4></div>
      
        <div class="card"><a><strong>Saúde Geral:</strong></a><h4 class="id-status">BOA</h4></div>
        <div class="card"><a><strong>Temperatura Interna:</strong></a><h4 class="id-status">24ºC</h4></div>
        <div class="card"><a><strong>Nível de Combustível:</strong></a><h4 class="id-status">25% !!</h4></div>
      
        <div class="card"><a><strong>Velocidade Atual:</strong></a><h4 class="id-status">98km/h</h4></div>
        <div class="card"><a><strong>Gerador Principal:</strong></a><h4 class="id-status">Amarelo !</h4></div>
        <div class="card"><a><strong>Tensão Bateria:</strong></a><h4 class="id-status">74.2V</h4></div>
      
        <div class="card"><a><strong>Temp. do Motor:</strong></a><h4 class="id-status">88ºC !</h4></div>
        <div class="card"><a><strong>Sistema de Freio:</strong></a><h4 class="id-status">Ruim !!</h4></div>
        <div class="card"><a><strong>Status de Tração:</strong></a><h4 class="id-status">Engatados</h4></div>
      </section>
      
      <div class="card gerenciar side-link" onclick="window.location.href='locomotivas.html'" style="cursor: pointer;"><a>Ir para Locomotivas</a></div>
    </main>
</body>

<script>
    const menuBtn = document.getElementById("menuBtn");
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("overlay");
    const id_status = document.querySelectorAll(".id-status");

    // Lógica de coloração dos status
    id_status.forEach(elemento => {
        const texto = elemento.textContent.trim();

        if (texto.includes("!!")) {
            elemento.classList.add("status-ruim");
        }
        else if (texto.includes("!")) {
            elemento.classList.add("status-alerta");
        }
        else {
            elemento.classList.add("status-bom");
        }
    });
    
    // Abrir/Fechar Menu
    menuBtn.addEventListener("click", () => {
        menuBtn.classList.toggle("active");
        sidebar.classList.toggle("active");
        overlay.classList.toggle("active");
    });

    // Fechar ao clicar no fundo escuro (overlay)
    overlay.addEventListener("click", () => {
        sidebar.classList.remove("active");
        menuBtn.classList.remove("active");
        overlay.classList.remove("active");
    });
</script>
</html>