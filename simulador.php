
<?php

require_once 'proteger.php';
require_once 'conexao.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$erros = [];
$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sensorId = filter_input(INPUT_POST, 'id_sensor', FILTER_VALIDATE_INT);
    $quantidade = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);

    if (!$sensorId || $sensorId <= 0) {
        $erros[] = 'Selecione um sensor válido.';
    }

    if ($quantidade === false || $quantidade === null
        || $quantidade < 1 || $quantidade > 200) {
        $erros[] = 'Informe uma quantidade entre 1 e 200.';
    }

    if (!$erros) {
        try {
            // Busca o sensor, sem exigir vínculo com uma locomotiva.
            $sqlSensor = "
                SELECT id_sensor, nome, tipo, local, id_trem, id_rota
                FROM sensor
                WHERE id_sensor = ?
            ";

            $stmtSensor = $conexao->prepare($sqlSensor);
            $stmtSensor->bind_param('i', $sensorId);
            $stmtSensor->execute();

            $sensor = $stmtSensor->get_result()->fetch_assoc();
            $stmtSensor->close();

            if (!$sensor) {
                throw new Exception('O sensor selecionado não existe.');
            }

            $tipo = $sensor['tipo'];

            // Este simulador gera valores para estes três tipos.
            if (!in_array($tipo, ['velocidade', 'temperatura', 'vibracao'], true)) {
                throw new Exception(
                    'Este tipo de sensor ainda não possui simulação configurada.'
                );
            }

            $sqlInsert = "
                INSERT INTO leituras (
                    velocidade_atual,
                    temperatura_do_motor,
                    nivel_de_combustivel,
                    vibracao_mm,
                    data_hora,
                    id_sensor
                ) VALUES (?, ?, ?, ?, ?, ?)
            ";

            $stmtInsert = $conexao->prepare($sqlInsert);

            $sqlUpdate = "
                UPDATE sensor
                SET ultima_leitura = ?
                WHERE id_sensor = ?
            ";

            $stmtUpdate = $conexao->prepare($sqlUpdate);

            $conexao->begin_transaction();

            try {
                $momento = time() - ($quantidade * 300);

                for ($i = 0; $i < $quantidade; $i++) {
                    $momento += 300;
                    $dataHora = date('Y-m-d H:i:s', $momento);

                    // Campos não medidos pelo sensor ficam como NULL.
                    $velocidade = null;
                    $temperatura = null;
                    $combustivel = null;
                    $vibracao = null;

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
                    }

                    $stmtInsert->bind_param(
                        'ddddsi',
                        $velocidade,
                        $temperatura,
                        $combustivel,
                        $vibracao,
                        $dataHora,
                        $sensorId
                    );

                    $stmtInsert->execute();

                    $stmtUpdate->bind_param(
                        'si',
                        $dataHora,
                        $sensorId
                    );

                    $stmtUpdate->execute();
                }

                $conexao->commit();

                $_SESSION['mensagem'] =
                    $quantidade . ' leituras salvas com sucesso para o sensor '
                    . $sensor['nome'] . '.';

                header('Location: simulador.php');
                exit;

            } catch (Throwable $e) {
                $conexao->rollback();
                throw $e;
            } finally {
                $stmtInsert->close();
                $stmtUpdate->close();
            }

        } catch (Throwable $e) {
            error_log('Erro no simulador: ' . $e->getMessage());
            $erros[] = 'Não foi possível salvar as leituras. '
                . 'Verifique a estrutura do banco e o log de erros do PHP.';
        }
    }
}

$mensagem = $_SESSION['mensagem'] ?? '';
unset($_SESSION['mensagem']);

// Lista todos os sensores, independentemente do local de instalação.
$sqlSensores = "
    SELECT
        s.id_sensor,
        s.nome,
        s.tipo,
        s.local,
        s.id_trem,
        s.id_rota,
        t.prefixo,
        t.modelo,
        r.origem,
        r.destino
    FROM sensor AS s
    LEFT JOIN trem AS t ON s.id_trem = t.id_trem
    LEFT JOIN rotas AS r ON s.id_rota = r.id_rota
    ORDER BY s.id_sensor
";

$trens = $conexao->query($sqlSensores);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulador de sensores</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
    <span class="marca">Frota Ferroviária</span>
    <nav>
        <a href="trens.php">Trens</a>
        <a href="painel.php">Painel</a>
        <a href="leituras.php">Leituras</a>
        <a href="simulador.php">Simulador</a>
        <a href="relatorios.php">Relatórios</a>
        <a href="logout.php">Sair</a>
    </nav>
</header>

<main>
    <h1>Simulador de sensores IoT</h1>

    <p class="apoio">
        Gere leituras simuladas a cada cinco minutos para um sensor
        instalado em uma locomotiva ou associado a uma rota.
    </p>

    <?php if ($mensagem !== ''): ?>
        <p class="aviso">
            <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <?php if ($erros): ?>
        <div class="aviso aviso-erro">
            <ul>
                <?php foreach ($erros as $erro): ?>
                    <li><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" class="formulario">
        <div class="linha">
            <div class="campo">
                <label for="id_sensor">Sensor</label>

                <select id="id_sensor" name="id_sensor" required>
                    <option value="">Selecione um sensor</option>

                    <?php while ($sensorLista = $trens->fetch_assoc()): ?>
                        <option
                            value="<?= (int) $sensorLista['id_sensor'] ?>"
                            <?= isset($sensorId) && (int) $sensorId === (int) $sensorLista['id_sensor'] ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($sensorLista['nome'], ENT_QUOTES, 'UTF-8') ?>
                            — <?= htmlspecialchars($sensorLista['tipo'], ENT_QUOTES, 'UTF-8') ?>
                            — <?= htmlspecialchars($sensorLista['local'] ?? 'Local não informado', ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($sensorLista['id_trem'] !== null): ?>
                                — <?= htmlspecialchars($sensorLista['prefixo'] ?? $sensorLista['modelo'] ?? 'Trem', ENT_QUOTES, 'UTF-8') ?>
                            <?php elseif ($sensorLista['id_rota'] !== null): ?>
                                — Rota <?= (int) $sensorLista['id_rota'] ?>
                                (<?= htmlspecialchars($sensorLista['origem'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                - <?= htmlspecialchars($sensorLista['destino'] ?? '', ENT_QUOTES, 'UTF-8') ?>)
                            <?php endif; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="campo">
                <label for="quantidade">Quantidade de leituras</label>
                <input
                    type="number"
                    id="quantidade"
                    name="quantidade"
                    min="1"
                    max="200"
                    value="<?= isset($quantidade) && $quantidade >= 1 && $quantidade <= 200 ? (int) $quantidade : 50 ?>"
                    required
                >
            </div>
        </div>

        <div class="acoes">
            <button type="submit" class="botao botao-primario">
                Gerar leituras
            </button>
        </div>
    </form>
</main>

</body>
</html>
