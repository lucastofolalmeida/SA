
<?php


require_once __DIR__ . '/proteger.php';
require_once __DIR__ . '/notificacoes_alertas.php';

?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>FerroHub - Leituras e Alertas</title>

    <link
        rel="stylesheet"
        href="../styles/leitura.css?v=<?= filemtime(__DIR__ . '/../styles/leitura.css') ?>">
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

        <a class="leituras-btn" href="leitura.php">
            <img src="../assets/sino.png" alt="">
            <span>Leituras</span>
        </a>

        <a class="logout-btn" href="logout.php">
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

        <h2>Leituras e Alertas</h2>

        <section class="resumo-alertas">

    <div class="card-resumo critico">
        <div class="icone-alerta">!</div>

        <p>Alertas críticos</p>

        <strong><?= (int) $totalCriticos ?></strong>
    </div>

    <div class="card-resumo atencao">
        <div class="icone-alerta">⚠</div>

        <p>Alertas de atenção</p>

        <strong><?= (int) $totalAtencao ?></strong>
    </div>

</section>

        <!-- Filtros -->
        <form method="GET" action="" class="filtros">

            <label for="severidade">
                Severidade

                <select name="severidade" id="severidade">
                    <option value="">Todas</option>

                    <option
                        value="critica"
                        <?= $filtroSeveridade === 'critica' ? 'selected' : '' ?>>
                        Crítica
                    </option>

                    <option
                        value="atencao"
                        <?= $filtroSeveridade === 'atencao' ? 'selected' : '' ?>>
                        Atenção
                    </option>
                </select>
            </label>

            <label for="tipo">
                Tipo de leitura

                <select name="tipo" id="tipo">
                    <option value="">Todos</option>

                    <?php foreach ($regras as $chave => $regra): ?>
                        <option
                            value="<?= e($chave) ?>"
                            <?= $filtroTipo === $chave ? 'selected' : '' ?>>
                            <?= e($regra['rotulo']) ?>
                        </option>
                    <?php endforeach; ?>

                </select>
            </label>

            <button type="submit">Filtrar</button>

            <a href="?">Limpar</a>

        </form>

        <!-- Lista de alertas -->
        <section class="lista-alertas">

            <?php if (empty($visiveis)): ?>

                <p class="vazio">
                    Nenhum alerta encontrado para os filtros selecionados.
                </p>

            <?php else: ?>

                <?php foreach ($visiveis as $a): ?>

                    <article class="alerta <?= e($a['severidade']) ?>">

                        <div class="alerta-conteudo">

                            <h3>
                                <?= e($a['rotulo']) ?>

                                <?= $a['severidade'] === 'critica'
                                    ? '— muito acima do limite'
                                    : '— acima do limite' ?>
                            </h3>

                            <p>
                                <strong>Locomotiva:</strong>
                                <?= e($a['locomotiva']) ?>
                            </p>

                            <p>
                                <strong>Data e hora:</strong>
                                <?= e($a['data_hora']) ?>
                            </p>

                            <p>
                                <strong>Valor registrado:</strong>
                                <?= e($a['valor']) ?>
                                <?= e($a['unidade']) ?>
                            </p>

                            <p>
                                <strong>Limite permitido:</strong>
                                <?= e($a['limite']) ?>
                                <?= e($a['unidade']) ?>
                            </p>

                        </div>

                        <span class="selo <?= e($a['severidade']) ?>">
                            <?= $a['severidade'] === 'critica'
                                ? 'CRÍTICO'
                                : 'ATENÇÃO' ?>
                        </span>

                    </article>

                <?php endforeach; ?>

            <?php endif; ?>

        </section>

    </main>

    <!-- Funcionamento do menu lateral -->
    <script>
        const menuBtn = document.getElementById('menuBtn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

        function fecharMenu() {
            menuBtn.classList.remove('active');
            sidebar.classList.remove('active');
            overlay.classList.remove('active');

            menuBtn.setAttribute('aria-expanded', 'false');
            menuBtn.setAttribute('aria-label', 'Abrir menu');
        }

        menuBtn.addEventListener('click', () => {
            const aberto = sidebar.classList.toggle('active');

            menuBtn.classList.toggle('active', aberto);
            overlay.classList.toggle('active', aberto);

            menuBtn.setAttribute('aria-expanded', String(aberto));
            menuBtn.setAttribute(
                'aria-label',
                aberto ? 'Fechar menu' : 'Abrir menu'
            );
        });

        overlay.addEventListener('click', fecharMenu);

        document.addEventListener('keydown', (evento) => {
            if (evento.key === 'Escape') {
                fecharMenu();
            }
        });
    </script>

</body>
</html>
