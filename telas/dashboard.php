<?php

require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/notificacoes_alertas.php';

if (!isset($pdo)) {
    die('Erro: conexão com o banco não foi iniciada.');
}

// 1. Contagem de trens por status
$stmtTrens = $pdo->query('
    SELECT status, COUNT(*) AS total 
    FROM trem 
    GROUP BY status
');
$trensPorStatus = $stmtTrens->fetchAll(PDO::FETCH_ASSOC);

// 2. Alertas ativos por severidade



// 3. Sensores com leituras crítica



// 4. Viagens em andamento
$stmtRotas = $pdo->prepare('
    SELECT id_rota, origem, destino, hora_partida, status 
    FROM rota 
    WHERE status = :status
');
$stmtRotas->execute(['status' => 'em_andamento']);
$viagensAndamento = $stmtRotas->fetchAll(PDO::FETCH_ASSOC);

// 5. Velocidade média da frota
$stmtVelocidade = $pdo->query('
    SELECT AVG(velocidade_atual) AS velocidade_media 
    FROM leituras
');
$velocidadeMedia = $stmtVelocidade->fetchColumn();

?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard Geral</title>
    <link rel="stylesheet" href="../styles/inicial.css" />
  </head>
  <body>

    <!-- Contagem de trens por status -->
    <div>
        <h1>Contagem de trens por status</h1>
        <ul>
            <?php foreach ($trensPorStatus as $item): ?>
                <li><strong><?= htmlspecialchars($item['status']) ?>:</strong> <?= $item['total'] ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Viagens em andamento -->
    <div>
        <h1>Viagens em andamento</h1>
        <?php if (empty($viagensAndamento)): ?>
            <p>Nenhuma viagem em andamento no momento.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($viagensAndamento as $rota): ?>
                    <li>
                        Rota #<?= $rota['id_rota'] ?>: <?= htmlspecialchars($rota['origem']) ?> &rarr; <?= htmlspecialchars($rota['destino']) ?> 
                        (Partida: <?= $rota['hora_partida'] ?>)
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Velocidade média da frota -->
    <div>
        <h1>Velocidade média da frota</h1>
        <p><strong><?= number_format((float)$velocidadeMedia, 2, ',', '.') ?> km/h</strong></p>
    </div>

  </body>
</html>