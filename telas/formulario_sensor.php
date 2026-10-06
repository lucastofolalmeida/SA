<?php

require 'proteger.php';
require 'conexao.php';

$id = (int) ($_GET['id'] ?? 0);

$nome = '';
$tipo = '';
$local = '';
$id_trem = '';

$erros = [];

$tipo_opcoes = [];

$resultado_enum = $conexao->query("SHOW COLUMNS FROM sensor LIKE 'tipo'");

if ($resultado_enum && $resultado_enum->num_rows > 0) {
    $coluna_tipo = $resultado_enum->fetch_assoc();

    if (preg_match("/^enum\('(.*)'\)$/", $coluna_tipo['Type'], $matches)) {
        $tipo_opcoes = str_getcsv($matches[1], ',', "'");

        $tipo_opcoes = array_map(function ($tipo) {
            return trim($tipo, " '\"");
        }, $tipo_opcoes);
    }
}

$local_opcoes = [
    'locomotiva' => 'Locomotiva',
    'trilho' => 'Trilho'
];

$locomotivas = [];

$resultado_trens = $conexao->query(
    'SELECT id_trem, prefixo FROM trem ORDER BY prefixo'
);

if ($resultado_trens) {

    while ($trem = $resultado_trens->fetch_assoc()) {

        $locomotivas[] = $trem;
    }
}

if ($id > 0 && $_SERVER['REQUEST_METHOD'] === 'GET') {

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
    $id_trem = $sensor['id_trem'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = (int) ($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');
    $local = trim($_POST['local'] ?? '');
    $id_trem = (int) ($_POST['id_trem'] ?? 0);



    if ($nome === '') {
        $erros[] = 'Informe o nome do sensor.';
    } elseif (mb_strlen($nome) > 80) {
        $erros[] = 'O nome do sensor deve ter no máximo 80 caracteres.';
    }


    if (!in_array($tipo, $tipo_opcoes, true)) {

        $erros[] = 'Selecione um tipo de sensor válido.';
    }


    if (!isset($local_opcoes[$local])) {

        $erros[] = 'Selecione um local válido.';
    }

    if ($local === 'locomotiva') {

        if ($id_trem <= 0) {

            $erros[] = 'Selecione a locomotiva associada.';
        } else {
            $stmt = $conexao->prepare(
                'SELECT id_trem FROM trem WHERE id_trem = ?'
            );

            $stmt->bind_param('i', $id_trem);

            $stmt->execute();

            $trem_existe = $stmt->get_result()->num_rows > 0;

            $stmt->close();


            if (!$trem_existe) {

                $erros[] = 'A locomotiva selecionada não existe.';
            }
        }
    } elseif ($local === 'trilho') {

        $erros[] = 'A associação com trilhos ainda não está disponível.';
    }

    if (count($erros) === 0) {

        if ($id > 0) {

            $stmt = $conexao->prepare(
                'UPDATE sensor
                 SET nome = ?, tipo = ?, local = ?, id_trem = ?
                 WHERE id_sensor = ?'
            );

            $stmt->bind_param(
                'sssii',
                $nome,
                $tipo,
                $local,
                $id_trem,
                $id
            );


            if ($stmt->execute()) {

                $_SESSION['mensagem'] =
                    'Sensor atualizado com sucesso!';
            } else {

                $_SESSION['mensagem'] =
                    'Não foi possível realizar a atualização.';
            }
        } else {

            $stmt = $conexao->prepare(
                'INSERT INTO sensor (nome, tipo, local, id_trem)
                 VALUES (?, ?, ?, ?)'
            );

            $stmt->bind_param(
                'sssi',
                $nome,
                $tipo,
                $local,
                $id_trem
            );


            if ($stmt->execute()) {

                $_SESSION['mensagem'] =
                    'Sensor cadastrado com sucesso!';
            } else {

                $_SESSION['mensagem'] =
                    'Não foi possível realizar o cadastro.';
            }
        }


        $stmt->close();

        header('Location: sensores.php');

        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        <?= $id > 0 ? 'Editar sensor' : 'Novo sensor' ?>
    </title>

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

        <h1>FerroHub</h1>

        <a
            class="logout-btn"
            href="logout.php">
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
                    alt="" />

                <span>Locomotivas</span>

            </a>


            <a
                href="relatorios.php"
                class="side-link">

                <img
                    src="../assets/relatorio.webp"
                    alt="" />

                <span>Relatórios</span>

            </a>


            <a
                href="sensores.php"
                class="side-link">

                <img
                    src="../assets/sensores.webp"
                    alt="" />

                <span>Sensores</span>

            </a>


            <a
                href="rotas.php"
                class="side-link">

                <img
                    src="../assets/rotas.png"
                    alt="" />

                <span>Rotas</span>

            </a>


            <a
                href="usuarios.php"
                class="side-link">

                <img
                    src="../assets/usuarios.png"
                    alt="" />

                <span>Usuários</span>

            </a>

        </nav>

    </aside>

    <div
        class="overlay"
        id="overlay"></div>

    <main>

        <h1>
            <?= $id > 0 ? 'Editar sensor' : 'Novo sensor' ?>
        </h1>

        <?php if (count($erros) > 0): ?>

            <div class="aviso aviso-erro">

                <ul>

                    <?php foreach ($erros as $item): ?>

                        <li>
                            <?= htmlspecialchars($item) ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>

        <form method="post">

            <input
                type="hidden"
                name="id"
                value="<?= (int) $id ?>">

            <div class="campo">

                <label for="nome">
                    Nome do sensor
                </label>

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

                <label for="tipo">
                    Tipo do sensor
                </label>

                <select
                    id="tipo"
                    name="tipo"
                    required>

                    <option value="">
                        Selecione o tipo
                    </option>

                    <?php foreach ($tipo_opcoes as $opcao): ?>

                        <option
                            value="<?= htmlspecialchars($opcao) ?>"
                            <?= $opcao === $tipo ? 'selected' : '' ?>>

                            <?= htmlspecialchars($opcao) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="campo">

                <label for="local">
                    Local
                </label>

                <select
                    id="local"
                    name="local"
                    required>

                    <option value="">
                        Selecione o local
                    </option>

                    <?php foreach ($local_opcoes as $valor => $rotulo): ?>

                        <option
                            value="<?= htmlspecialchars($valor) ?>"
                            <?= $valor === $local ? 'selected' : '' ?>>

                            <?= htmlspecialchars($rotulo) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="campo">

                <label for="id_trem">
                    Locomotiva associada
                </label>

                <select
                    id="id_trem"
                    name="id_trem">

                    <option value="">
                        Selecione uma locomotiva
                    </option>

                    <?php foreach ($locomotivas as $trem): ?>

                        <option
                            value="<?= (int) $trem['id_trem'] ?>"
                            <?= (int) $trem['id_trem'] === (int) $id_trem ? 'selected' : '' ?>>

                            <?= htmlspecialchars($trem['prefixo']) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="acoes">

                <a
                    href="sensores.php"
                    class="botao botao-secundario">
                    Cancelar
                </a>

                <button
                    type="submit"
                    class="botao botao-primario">

                    <?= $id > 0 ? 'Atualizar' : 'Cadastrar' ?>

                </button>

            </div>

        </form>

    </main>
    <script>
        const menuBtn =
            document.getElementById("menuBtn");

        const sidebar =
            document.getElementById("sidebar");

        const overlay =
            document.getElementById("overlay");


        menuBtn.addEventListener("click", () => {

            const aberto =
                sidebar.classList.toggle("active");

            menuBtn.classList.toggle("active", aberto);

            overlay.classList.toggle("active", aberto);

            menuBtn.setAttribute(
                "aria-expanded",
                aberto ? "true" : "false"
            );

        });


        overlay.addEventListener("click", () => {

            sidebar.classList.remove("active");

            menuBtn.classList.remove("active");

            overlay.classList.remove("active");

            menuBtn.setAttribute(
                "aria-expanded",
                "false"
            );

        });


        const localSelect =
            document.getElementById("local");

        const associacaoSelect =
            document.getElementById("id_trem");

        const associacaoLabel =
            document.querySelector(
                'label[for="id_trem"]'
            );


        function atualizarAssociacao() {

            if (localSelect.value === "locomotiva") {

                associacaoLabel.textContent =
                    "Locomotiva associada";

                associacaoSelect.disabled = false;

                associacaoSelect.innerHTML = `
                    <option value="">
                        Selecione uma locomotiva
                    </option>
                `;


                <?php foreach ($locomotivas as $trem): ?>

                    associacaoSelect.innerHTML += `
                        <option
                            value="<?= (int) $trem['id_trem'] ?>"
                        >
                            <?= htmlspecialchars(
                                $trem['prefixo'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </option>
                    `;

                <?php endforeach; ?>


                const idSelecionado =
                    <?= (int) $id_trem ?>;


                if (idSelecionado > 0) {

                    associacaoSelect.value =
                        idSelecionado;

                }


            } else if (localSelect.value === "trilho") {

                associacaoLabel.textContent =
                    "Trilho associado";

                associacaoSelect.disabled = true;

                associacaoSelect.innerHTML = `
                    <option value="">
                        Trilhos ainda não cadastrados
                    </option>
                `;


            } else {

                associacaoLabel.textContent =
                    "Locomotiva associada";

                associacaoSelect.disabled = true;

                associacaoSelect.innerHTML = `
                    <option value="">
                        Selecione o local primeiro
                    </option>
                `;

            }

        }


        localSelect.addEventListener(
            "change",
            atualizarAssociacao
        );


        atualizarAssociacao();
    </script>

</body>

</html>