<?php
require_once __DIR__ . '/proteger.php';
require_once __DIR__ . '/conexao.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$id = (int) ($_GET['id'] ?? 0);
$nome = '';
$tipo = '';
$local = '';
$id_trem = '';
$id_rota = '';
$erros = [];

$tipo_opcoes = [
    'temperatura',
    'vibracao',
    'velocidade',
    'pressao',
    'rotacao'
];

$local_opcoes = [
    'locomotiva' => 'Locomotiva',
    'rota' => 'Rota',
    'trilho' => 'Trilho'
];

$locomotivas = [];
$rotas = [];

$resultado = $conexao->query(
    'SELECT id_trem, prefixo, modelo FROM trem ORDER BY prefixo'
);
while ($linha = $resultado->fetch_assoc()) {
    $locomotivas[] = $linha;
}

$resultado = $conexao->query(
    'SELECT r.id_rota, r.origem, r.destino, r.data_viagem,
            r.status, t.prefixo
     FROM rotas r
     INNER JOIN trem t ON t.id_trem = r.id_trem
     ORDER BY r.data_viagem DESC, r.hora_partida DESC'
);
while ($linha = $resultado->fetch_assoc()) {
    $rotas[] = $linha;
}

if ($id > 0 && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $conexao->prepare(
        'SELECT * FROM sensor WHERE id_sensor = ?'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $sensor = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$sensor) {
        $_SESSION['mensagem'] = 'Sensor não encontrado.';
        header('Location: sensores.php');
        exit;
    }

    $nome = $sensor['nome'];
    $tipo = $sensor['tipo'];
    $local = $sensor['local'];
    $id_trem = $sensor['id_trem'] ?? '';
    $id_rota = $sensor['id_rota'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    $local = trim($_POST['local'] ?? '');

    $id_trem = (int) ($_POST['id_trem'] ?? 0);
    $id_rota = (int) ($_POST['id_rota'] ?? 0);

    if ($nome === '' || mb_strlen($nome) > 80) {
        $erros[] = 'Informe um nome de até 80 caracteres.';
    }

    if (!in_array($tipo, $tipo_opcoes, true)) {
        $erros[] = 'Selecione um tipo de sensor válido.';
    }

    if (!isset($local_opcoes[$local])) {
        $erros[] = 'Selecione um local válido.';
    }

    if ($local === 'locomotiva') {
        $id_rota = 0;

        $stmt = $conexao->prepare(
            'SELECT id_trem FROM trem WHERE id_trem = ?'
        );
        $stmt->bind_param('i', $id_trem);
        $stmt->execute();
        $existe = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if ($id_trem <= 0 || !$existe) {
            $erros[] = 'Selecione uma locomotiva válida.';
        }
    } elseif ($local === 'rota') {
        $id_trem = 0;

        $stmt = $conexao->prepare(
            'SELECT id_rota FROM rotas WHERE id_rota = ?'
        );
        $stmt->bind_param('i', $id_rota);
        $stmt->execute();
        $existe = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if ($id_rota <= 0 || !$existe) {
            $erros[] = 'Selecione uma rota válida.';
        }
    } elseif ($local === 'trilho') {
        $id_trem = 0;
        $id_rota = 0;
        $erros[] = 'A associação com trilhos ainda não está disponível.';
    }

    if (!$erros) {
        try {
            if ($id > 0) {
                // Editar sensor não gera novas leituras.
                $tremBanco = $id_trem > 0 ? $id_trem : null;
                $rotaBanco = $id_rota > 0 ? $id_rota : null;

                $stmt = $conexao->prepare(
                    'UPDATE sensor
                     SET nome = ?, tipo = ?, local = ?,
                         id_trem = ?, id_rota = ?
                     WHERE id_sensor = ?'
                );
                $stmt->bind_param(
                    'sssiii',
                    $nome,
                    $tipo,
                    $local,
                    $tremBanco,
                    $rotaBanco,
                    $id
                );
                $stmt->execute();
                $stmt->close();

                $_SESSION['mensagem'] =
                    'Sensor atualizado com sucesso.';
            } else {
                // Cadastrar sensor e gerar suas 50 leituras
                // dentro da mesma transação.
                $conexao->begin_transaction();

                try {
                    $tremBanco = $id_trem > 0 ? $id_trem : null;
                    $rotaBanco = $id_rota > 0 ? $id_rota : null;

                    $stmt = $conexao->prepare(
                        'INSERT INTO sensor
                         (nome, tipo, local, id_trem, id_rota)
                         VALUES (?, ?, ?, ?, ?)'
                    );
                    $stmt->bind_param(
                        'sssii',
                        $nome,
                        $tipo,
                        $local,
                        $tremBanco,
                        $rotaBanco
                    );
                    $stmt->execute();

                    $novoSensorId = $conexao->insert_id;
                    $stmt->close();

                    $stmtLeitura = $conexao->prepare(
                        'INSERT INTO leituras
                         (velocidade_atual, temperatura_do_motor,
                          nivel_de_combustivel, vibracao_mm,
                          pressao, rotacao, data_hora, id_sensor)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                    );

                    $stmtAtualiza = $conexao->prepare(
                        'UPDATE sensor
                         SET ultima_leitura = ?
                         WHERE id_sensor = ?'
                    );

                    $agora = time();
                    $ultimaData = date('Y-m-d H:i:s', $agora - 49 * 300);

                    for ($i = 0; $i < 50; $i++) {
                        $velocidade = null;
                        $temperatura = null;
                        $combustivel = null;
                        $vibracao = null;
                        $pressao = null;
                        $rotacao = null;

                        switch ($tipo) {
                            case 'velocidade':
                                $velocidade = rand(0, 11000) / 100;
                                break;
                            case 'temperatura':
                                $temperatura = rand(6000, 12000) / 100;
                                break;
                            case 'vibracao':
                                $vibracao = rand(50, 1200) / 100;
                                break;
                            case 'pressao':
                                $pressao = rand(8000, 12000) / 100;
                                break;
                            case 'rotacao':
                                $rotacao = rand(500, 3000);
                                break;
                        }

                        $dataHora = date(
                            'Y-m-d H:i:s',
                            $agora - (49 - $i) * 300
                        );
                        $ultimaData = $dataHora;

                        $stmtLeitura->bind_param(
                            'ddddddsi',
                            $velocidade,
                            $temperatura,
                            $combustivel,
                            $vibracao,
                            $pressao,
                            $rotacao,
                            $dataHora,
                            $novoSensorId
                        );
                        $stmtLeitura->execute();
                    }

                    $stmtLeitura->close();

                    $stmtAtualiza->bind_param(
                        'si',
                        $ultimaData,
                        $novoSensorId
                    );
                    $stmtAtualiza->execute();
                    $stmtAtualiza->close();

                    $conexao->commit();

                    $_SESSION['mensagem'] =
                        'Sensor cadastrado com sucesso! ' .
                        'Foram geradas 50 leituras simuladas.';
                } catch (Throwable $e) {
                    $conexao->rollback();
                    throw $e;
                }
            }

            header('Location: sensores.php');
            exit;
        } catch (Throwable $e) {
            error_log('Erro no cadastro de sensor: ' . $e->getMessage());
            $erros[] =
                'Não foi possível salvar o sensor e gerar as leituras. ' .
                'Confira o log de erros do PHP e a estrutura do banco.';
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id > 0 ? 'Editar sensor' : 'Novo sensor' ?> - FerroHub</title>
    <link rel="stylesheet"
        href="../styles/formulario.css?v=<?= filemtime(__DIR__ . '/../styles/formulario.css') ?>">
</head>

<body>
    <header class="topbar">
        <button class="menu-btn" id="menuBtn" type="button"
            aria-label="Abrir menu" aria-controls="sidebar"
            aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <a href="home.php">
            <h1>FerroHub</h1>
        </a>
        <a class="leituras-btn" href="leitura.php">
            <img src="../assets/sino.png" alt="">
            <span>Leituras</span>
        </a>
        <a class="logout-btn" href="logout.php">Sair</a>
    </header>

    <aside class="sidebar" id="sidebar">
        <nav>
            <a href="locomotivas.php" class="side-link">
                <img src="../assets/locomotiva.png" alt=""><span>Locomotivas</span>
            </a>
            <a href="relatorios.php" class="side-link">
                <img src="../assets/relatorio.webp" alt=""><span>Relatórios</span>
            </a>
            <a href="sensores.php" class="side-link">
                <img src="../assets/sensores.webp" alt=""><span>Sensores</span>
            </a>
            <a href="rotas.php" class="side-link">
                <img src="../assets/rotas.png" alt=""><span>Rotas</span>
            </a>
            <a href="usuarios.php" class="side-link">
                <img src="../assets/usuarios.png" alt=""><span>Usuários</span>
            </a>
        </nav>
    </aside>
    <div class="overlay" id="overlay"></div>

    <main>
        <h1><?= $id > 0 ? 'Editar sensor' : 'Novo sensor' ?></h1>

        <?php if ($erros): ?>
            <div class="aviso aviso-erro">
                <ul>
                    <?php foreach ($erros as $erro): ?>
                        <li><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($id === 0): ?>
            <p>Após o cadastro, serão criadas automaticamente 50 leituras simuladas.</p>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="id" value="<?= (int) $id ?>">

            <div class="campo">
                <label for="nome">Nome do sensor</label>
                <input type="text" id="nome" name="nome" maxlength="80"
                    value="<?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>"
                    required>
            </div>

            <div class="campo">
                <label for="tipo">Tipo do sensor</label>
                <select id="tipo" name="tipo" required>
                    <option value="">Selecione o tipo</option>
                    <?php foreach ($tipo_opcoes as $opcao): ?>
                        <option value="<?= $opcao ?>"
                            <?= $opcao === $tipo ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst($opcao), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="local">Local</label>
                <select id="local" name="local" required>
                    <option value="">Selecione o local</option>
                    <?php foreach ($local_opcoes as $valor => $rotulo): ?>
                        <option value="<?= $valor ?>"
                            <?= $valor === $local ? 'selected' : '' ?>>
                            <?= $rotulo ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo" id="campo-trem">
                <label for="id_trem">Locomotiva associada</label>
                <select id="id_trem" name="id_trem">
                    <option value="">Selecione uma locomotiva</option>
                    <?php foreach ($locomotivas as $trem): ?>
                        <option value="<?= (int) $trem['id_trem'] ?>"
                            <?= (int) $trem['id_trem'] === (int) $id_trem ? 'selected' : '' ?>>
                            <?= htmlspecialchars(
                                ($trem['prefixo'] ?: $trem['modelo']),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo" id="campo-rota">
                <label for="id_rota">Rota associada</label>
                <select id="id_rota" name="id_rota">
                    <option value="">Selecione uma rota</option>
                    <?php foreach ($rotas as $rota): ?>
                        <option value="<?= (int) $rota['id_rota'] ?>"
                            <?= (int) $rota['id_rota'] === (int) $id_rota ? 'selected' : '' ?>>
                            <?= htmlspecialchars(
                                $rota['origem'] . ' → ' . $rota['destino'] .
                                    ' | ' . date('d/m/Y', strtotime($rota['data_viagem'])) .
                                    ' | ' . $rota['prefixo'] . ' | ' . $rota['status'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="acoes">
                <a href="sensores.php" class="botao botao-secundario">Cancelar</a>
                <button type="submit" class="botao botao-primario">
                    <?= $id > 0 ? 'Atualizar' : 'Cadastrar e gerar 50 leituras' ?>
                </button>
            </div>
        </form>
    </main>

    <script>
        const menuBtn = document.getElementById('menuBtn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

        menuBtn.addEventListener('click', () => {
            const aberto = sidebar.classList.toggle('active');
            menuBtn.classList.toggle('active', aberto);
            overlay.classList.toggle('active', aberto);
            menuBtn.setAttribute('aria-expanded', String(aberto));
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            menuBtn.classList.remove('active');
            overlay.classList.remove('active');
            menuBtn.setAttribute('aria-expanded', 'false');
        });

        const localSelect = document.getElementById('local');
        const campoTrem = document.getElementById('campo-trem');
        const campoRota = document.getElementById('campo-rota');
        const selectTrem = document.getElementById('id_trem');
        const selectRota = document.getElementById('id_rota');

        function atualizarAssociacao() {
            const local = localSelect.value;
            campoTrem.hidden = local !== 'locomotiva';
            campoRota.hidden = local !== 'rota';
            selectTrem.disabled = local !== 'locomotiva';
            selectRota.disabled = local !== 'rota';
            selectTrem.required = local === 'locomotiva';
            selectRota.required = local === 'rota';
        }

        localSelect.addEventListener('change', atualizarAssociacao);
        atualizarAssociacao();
    </script>
</body>

</html>