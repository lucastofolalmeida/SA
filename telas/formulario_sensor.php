<?php

require 'proteger.php';
require 'conexao.php';

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

// Busca locomotivas
$locomotivas = [];

$resultado_trens = $conexao->query(
    'SELECT id_trem, prefixo
     FROM trem
     ORDER BY prefixo'
);

if ($resultado_trens) {
    while ($trem = $resultado_trens->fetch_assoc()) {
        $locomotivas[] = $trem;
    }
}

// Busca rotas e o prefixo da locomotiva associada
$rotas = [];

$resultado_rotas = $conexao->query(
    'SELECT
        r.id_rota,
        r.origem,
        r.destino,
        r.data_viagem,
        r.status,
        t.prefixo
     FROM rotas r
     INNER JOIN trem t ON t.id_trem = r.id_trem
     ORDER BY r.data_viagem DESC, r.hora_partida DESC'
);

if ($resultado_rotas) {
    while ($rota = $resultado_rotas->fetch_assoc()) {
        $rotas[] = $rota;
    }
}

// Carrega o sensor para edição
if ($id > 0 && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conexao->prepare(
        'SELECT *
         FROM sensor
         WHERE id_sensor = ?'
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

// Processa o formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    $local = trim($_POST['local'] ?? '');

    $id_trem = (int) ($_POST['id_trem'] ?? 0);
    $id_rota = (int) ($_POST['id_rota'] ?? 0);

    // Validação do nome
    if ($nome === '') {
        $erros[] = 'Informe o nome do sensor.';
    } elseif (mb_strlen($nome) > 80) {
        $erros[] = 'O nome do sensor deve ter no máximo 80 caracteres.';
    }

    // Validação do tipo
    if (!in_array($tipo, $tipo_opcoes, true)) {
        $erros[] = 'Selecione um tipo de sensor válido.';
    }

    // Validação do local
    if (!isset($local_opcoes[$local])) {
        $erros[] = 'Selecione um local válido.';
    }

    // Cada sensor pode ter somente uma associação
    if ($local === 'locomotiva') {
        $id_rota = null;

        if ($id_trem <= 0) {
            $erros[] = 'Selecione a locomotiva associada.';
        } else {
            $stmt = $conexao->prepare(
                'SELECT id_trem
                 FROM trem
                 WHERE id_trem = ?'
            );

            $stmt->bind_param('i', $id_trem);
            $stmt->execute();

            $existe = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if (!$existe) {
                $erros[] = 'A locomotiva selecionada não existe.';
            }
        }
    } elseif ($local === 'rota') {
        $id_trem = null;

        if ($id_rota <= 0) {
            $erros[] = 'Selecione a rota associada.';
        } else {
            $stmt = $conexao->prepare(
                'SELECT id_rota
                 FROM rotas
                 WHERE id_rota = ?'
            );

            $stmt->bind_param('i', $id_rota);
            $stmt->execute();

            $existe = $stmt->get_result()->num_rows > 0;
            $stmt->close();

            if (!$existe) {
                $erros[] = 'A rota selecionada não existe.';
            }
        }
    } elseif ($local === 'trilho') {
        $id_trem = null;
        $id_rota = null;

        $erros[] = 'A associação com trilhos ainda não está disponível.';
    } else {
        $id_trem = null;
        $id_rota = null;
    }

    // Salva se não houver erros
    if (count($erros) === 0) {
        if ($id > 0) {
            $stmt = $conexao->prepare(
                'UPDATE sensor
                 SET nome = ?,
                     tipo = ?,
                     local = ?,
                     id_trem = ?,
                     id_rota = ?
                 WHERE id_sensor = ?'
            );

            $stmt->bind_param(
                'sssiii',
                $nome,
                $tipo,
                $local,
                $id_trem,
                $id_rota,
                $id
            );

            $sucesso = $stmt->execute();
            $stmt->close();

            $_SESSION['mensagem'] = $sucesso
                ? 'Sensor atualizado com sucesso!'
                : 'Não foi possível realizar a atualização.';
        } else {
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
                $id_trem,
                $id_rota
            );

            $sucesso = $stmt->execute();
            $stmt->close();

            $_SESSION['mensagem'] = $sucesso
                ? 'Sensor cadastrado com sucesso!'
                : 'Não foi possível realizar o cadastro.';
        }

        header('Location: sensores.php');
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $id > 0 ? 'Editar sensor' : 'Novo sensor' ?></title>

    <link
        rel="stylesheet"
        href="../styles/formulario.css?v=<?= filemtime(__DIR__ . '/../styles/formulario.css') ?>">
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
        </nav>
    </aside>

    <div class="overlay" id="overlay"></div>

    <main>
        <h1><?= $id > 0 ? 'Editar sensor' : 'Novo sensor' ?></h1>

        <?php if (count($erros) > 0): ?>
            <div class="aviso aviso-erro">
                <ul>
                    <?php foreach ($erros as $item): ?>
                        <li><?= htmlspecialchars($item) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="id" value="<?= (int) $id ?>">

            <div class="campo">
                <label for="nome">Nome do sensor</label>
                <input
                    type="text"
                    id="nome"
                    name="nome"
                    maxlength="80"
                    value="<?= htmlspecialchars($nome) ?>"
                    placeholder="Digite o nome do sensor"
                    required>
            </div>

            <div class="campo">
                <label for="tipo">Tipo do sensor</label>
                <select id="tipo" name="tipo" required>
                    <option value="">Selecione o tipo</option>

                    <?php foreach ($tipo_opcoes as $opcao): ?>
                        <option
                            value="<?= htmlspecialchars($opcao) ?>"
                            <?= $opcao === $tipo ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst($opcao)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="local">Local</label>
                <select id="local" name="local" required>
                    <option value="">Selecione o local</option>

                    <?php foreach ($local_opcoes as $valor => $rotulo): ?>
                        <option
                            value="<?= htmlspecialchars($valor) ?>"
                            <?= $valor === $local ? 'selected' : '' ?>>
                            <?= htmlspecialchars($rotulo) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo" id="campo-trem">
                <label for="id_trem">Locomotiva associada</label>
                <select id="id_trem" name="id_trem">
                    <option value="">Selecione uma locomotiva</option>

                    <?php foreach ($locomotivas as $trem): ?>
                        <option
                            value="<?= (int) $trem['id_trem'] ?>"
                            <?= (int) $trem['id_trem'] === (int) $id_trem ? 'selected' : '' ?>>
                            <?= htmlspecialchars($trem['prefixo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo" id="campo-rota">
                <label for="id_rota">Rota associada</label>
                <select id="id_rota" name="id_rota">
                    <option value="">Selecione uma rota</option>

                    <?php foreach ($rotas as $rota): ?>
                        <option
                            value="<?= (int) $rota['id_rota'] ?>"
                            <?= (int) $rota['id_rota'] === (int) $id_rota ? 'selected' : '' ?>>
                            <?= htmlspecialchars(
                                $rota['origem'] . ' → ' .
                                    $rota['destino'] . ' | ' .
                                    date('d/m/Y', strtotime($rota['data_viagem'])) . ' | ' .
                                    $rota['prefixo'] . ' | ' .
                                    $rota['status']
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="acoes">
                <a href="sensores.php" class="botao botao-secundario">Cancelar</a>

                <button type="submit" class="botao botao-primario">
                    <?= $id > 0 ? 'Atualizar' : 'Cadastrar' ?>
                </button>
            </div>
        </form>
    </main>

    <script>
        const menuBtn = document.getElementById("menuBtn");
        const sidebar = document.getElementById("sidebar");
        const overlay = document.getElementById("overlay");

        menuBtn.addEventListener("click", () => {
            const aberto = sidebar.classList.toggle("active");
            menuBtn.classList.toggle("active", aberto);
            overlay.classList.toggle("active", aberto);
            menuBtn.setAttribute("aria-expanded", aberto ? "true" : "false");
        });

        overlay.addEventListener("click", () => {
            sidebar.classList.remove("active");
            menuBtn.classList.remove("active");
            overlay.classList.remove("active");
            menuBtn.setAttribute("aria-expanded", "false");
        });

        const localSelect = document.getElementById("local");
        const campoTrem = document.getElementById("campo-trem");
        const campoRota = document.getElementById("campo-rota");
        const selectTrem = document.getElementById("id_trem");
        const selectRota = document.getElementById("id_rota");

        function atualizarAssociacao() {
            const local = localSelect.value;

            campoTrem.hidden = local !== "locomotiva";
            campoRota.hidden = local !== "rota";

            selectTrem.disabled = local !== "locomotiva";
            selectRota.disabled = local !== "rota";

            selectTrem.required = local === "locomotiva";
            selectRota.required = local === "rota";
        }

        localSelect.addEventListener("change", atualizarAssociacao);
        atualizarAssociacao();
    </script>

</body>

</html>