<?php

require_once __DIR__ . '/proteger.php';
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/notificacoes_alertas.php';

// Validação usando $conexao (mysqli) em vez de $pdo
if (!isset($conexao) || $conexao->connect_error) {
    die('Erro: conexão com o banco não foi iniciada.');
}

// 1. Contagem de trens por status
$sqlTrens = '
    SELECT status, COUNT(*) AS total 
    FROM trem 
    GROUP BY status
';
$resTrens = $conexao->query($sqlTrens);
$trensPorStatus = $resTrens ? $resTrens->fetch_all(MYSQLI_ASSOC) : [];

// 2. Alertas ativos por severidade
$todosAlertas = obterAlertasProcessados($conexao);

$totalCriticos = count(array_filter($todosAlertas, fn($a) => $a['severidade'] === 'critica'));
$totalAtencao  = count(array_filter($todosAlertas, fn($a) => $a['severidade'] === 'atencao'));

// 3. Sensores com leituras críticas
$sensoresCriticos = array_values(array_filter(
    $todosAlertas,
    fn($alerta) => $alerta['severidade'] === 'critica'
));

// 4. Viagens em andamento
$stmtRotas = $conexao->prepare('
    SELECT id_rota, origem, destino, hora_partida, status 
    FROM rotas 
    WHERE status = ?
');
$statusAndamento = 'em_andamento';
$stmtRotas->bind_param('s', $statusAndamento);
$stmtRotas->execute();
$resRotas = $stmtRotas->get_result();
$viagensAndamento = $resRotas ? $resRotas->fetch_all(MYSQLI_ASSOC) : [];

// 5. Velocidade média da frota
$sqlVelocidade = '
    SELECT AVG(velocidade_atual) AS velocidade_media 
    FROM leituras
';
$resVelocidade = $conexao->query($sqlVelocidade);
$dadosVelocidade = $resVelocidade ? $resVelocidade->fetch_assoc() : null;
$velocidadeMedia = $dadosVelocidade['velocidade_media'] ?? 0;

?>
<!doctype html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FerroHub - Dashboard</title>
  <link rel="stylesheet" href="../styles/dashboard.css?v=<?= filemtime(__DIR__ . '/../styles/dashboard.css') ?>" />
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

    <a class="logout-btn" href="logout.php">Sair</a>
  </header>

  <aside class="sidebar" id="sidebar">
    <nav>
      <a href="locomotivas.php" class="side-link">
        <img src="../assets/locomotiva.png" alt="">
        <span>Locomotivas</span>
      </a>
      <a href="relatorios.php" class="side-link">
        <img src="../assets/relatorio.webp" alt="">
        <span>Relatórios</span>
      </a>
      <a href="sensores.php" class="side-link">
        <img src="../assets/sensores.webp" alt="">
        <span>Sensores</span>
      </a>
      <a href="rotas.php" class="side-link">
        <img src="../assets/rotas.png" alt="">
        <span>Rotas</span>
      </a>
      <a href="usuarios.php" class="side-link">
        <img src="../assets/usuarios.png" alt="">
        <span>Usuários</span>
      </a>
      <a href="dashboard.php" class="side-link">
        <img src="../assets/dashboard.png" alt="">
        <span>Dashboard</span>
      </a>
    </nav>
  </aside>

  <div class="overlay" id="overlay"></div>

  <main class="container">
    <div class="dashboard-grid">

      <!-- Velocidade média da frota -->
      <div class="dashboard-card destaque">
        <h1>Velocidade Média da Frota</h1>
        <div class="card-content">
          <div class="velocidade-valor">
            <?= number_format((float)$velocidadeMedia, 2, ',', '.') ?> <small style="font-size: 1.5rem;">km/h</small>
          </div>
        </div>
      </div>

      <!-- Contagem de trens por status -->
      <div class="dashboard-card">
        <h1>Contagem de Trens por Status</h1>
        <div class="card-content">
          <ul class="dashboard-list">
            <?php foreach ($trensPorStatus as $item): ?>
              <li>
                <span><strong><?= htmlspecialchars($item['status']) ?></strong></span>
                <span class="badge-count"><?= $item['total'] ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>

      <!-- Alertas ativos por severidade -->
      <div class="dashboard-card">
        <h1>Alertas Ativos por Severidade</h1>
        <div class="card-content">
          <ul class="dashboard-list">
            <li>
              <span>Crítica</span>
              <span class="badge-count badge-critica"><?= $totalCriticos ?></span>
            </li>
            <li>
              <span>Atenção</span>
              <span class="badge-count badge-atencao"><?= $totalAtencao ?></span>
            </li>
            <li>
              <span>Total de Alertas</span>
              <span class="badge-count"><?= count($todosAlertas) ?></span>
            </li>
          </ul>
        </div>
      </div>

      <!-- Sensores com leitura crítica -->
      <div class="dashboard-card">
        <h1>Sensores em Estado Crítico</h1>
        <div class="card-content">
          <?php if (empty($sensoresCriticos)): ?>
            <p class="mensagem-vazia">Nenhum sensor com leitura crítica no momento.</p>
          <?php else: ?>
            <ul class="dashboard-list">
              <?php foreach ($sensoresCriticos as $alerta): ?>
                <li class="critico-item">
                  <div class="critico-header">
                    <span><?= htmlspecialchars($alerta['nome_sensor']) ?></span>
                    <span><?= number_format($alerta['valor'], 2, ',', '.') ?> <?= htmlspecialchars($alerta['unidade']) ?></span>
                  </div>
                  <div class="critico-detalhe">
                    <?= htmlspecialchars($alerta['locomotiva']) ?> — Limite: <?= number_format($alerta['limite'], 2, ',', '.') ?> <?= htmlspecialchars($alerta['unidade']) ?>
                  </div>
                  <span class="critico-tempo"><?= date('d/m/Y H:i', strtotime($alerta['data_hora'])) ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>

      <!-- Viagens em andamento -->
      <div class="dashboard-card">
        <h1>Viagens em Andamento</h1>
        <div class="card-content">
          <?php if (empty($viagensAndamento)): ?>
            <p class="mensagem-vazia">Nenhuma viagem em andamento no momento.</p>
          <?php else: ?>
            <ul class="dashboard-list">
              <?php foreach ($viagensAndamento as $rota): ?>
                <li>
                  <div>
                    <strong>Rota #<?= $rota['id_rota'] ?></strong><br>
                    <small><?= htmlspecialchars($rota['origem']) ?> &rarr; <?= htmlspecialchars($rota['destino']) ?></small>
                  </div>
                  <span class="badge-count"><?= htmlspecialchars($rota['hora_partida']) ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>

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