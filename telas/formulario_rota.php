<?php

require 'proteger.php';
require 'conexao.php';


$id = (int) ($_GET['id'] ?? 0);

$origem = '';
$destino = '';
$data_viagem = '';
$hora_partida = '';
$hora_chegada = '';
$status = 'programada';
$id_trem = '';

$erros = [];


// Busca os trens cadastrados

$trens = [];

$resultado_trens = $conexao->query(
    'SELECT id_trem, prefixo, modelo
     FROM trem
     ORDER BY prefixo'
);

if ($resultado_trens) {

    while ($linha_trem = $resultado_trens->fetch_assoc()) {

        $trens[] = $linha_trem;
    }
}


// Opções de status

$status_opcoes = [
    'programada' => 'Programada',
    'em andamento' => 'Em andamento',
    'concluída' => 'Concluída',
    'cancelada' => 'Cancelada'
];


// Carrega os dados quando estiver editando

if ($id > 0 && $_SERVER['REQUEST_METHOD'] === 'GET') {

    $stmt = $conexao->prepare(
        'SELECT *
         FROM rotas
         WHERE id_rota = ?'
    );

    $stmt->bind_param('i', $id);

    $stmt->execute();

    $rota = $stmt->get_result()->fetch_assoc();

    $stmt->close();


    if (!$rota) {

        $_SESSION['mensagem'] = 'Rota não encontrada.';

        header('Location: rotas.php');

        exit;
    }


    $id_trem = $rota['id_trem'];
    $origem = $rota['origem'];
    $destino = $rota['destino'];
    $data_viagem = $rota['data_viagem'];
    $hora_partida = $rota['hora_partida'];
    $hora_chegada = $rota['hora_chegada'];
    $status = $rota['status'];
}


// Processa o formulário

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = (int) ($_POST['id'] ?? 0);

    $id_trem = (int) ($_POST['id_trem'] ?? 0);

    $origem = trim($_POST['origem'] ?? '');

    $destino = trim($_POST['destino'] ?? '');

    $data_viagem = trim($_POST['data_viagem'] ?? '');

    $hora_partida = trim($_POST['hora_partida'] ?? '');

    $hora_chegada = trim($_POST['hora_chegada'] ?? '');

    $status = trim($_POST['status'] ?? '');


    // Validação do trem

    if ($id_trem <= 0) {

        $erros[] = 'Selecione um trem.';
    } else {

        $stmt = $conexao->prepare(
            'SELECT id_trem
             FROM trem
             WHERE id_trem = ?'
        );

        $stmt->bind_param('i', $id_trem);

        $stmt->execute();

        $trem_existe =
            $stmt->get_result()->num_rows > 0;

        $stmt->close();


        if (!$trem_existe) {

            $erros[] =
                'O trem selecionado não existe.';
        }
    }


    // Validação da origem

    if ($origem === '') {

        $erros[] =
            'Informe a origem da rota.';
    } elseif (mb_strlen($origem) > 80) {

        $erros[] =
            'A origem deve ter no máximo 80 caracteres.';
    }


    // Validação do destino

    if ($destino === '') {

        $erros[] =
            'Informe o destino da rota.';
    } elseif (mb_strlen($destino) > 80) {

        $erros[] =
            'O destino deve ter no máximo 80 caracteres.';
    }


    // Origem e destino não podem ser iguais

    if (
        $origem !== '' &&
        $destino !== '' &&
        mb_strtolower($origem) === mb_strtolower($destino)
    ) {

        $erros[] =
            'A origem e o destino não podem ser iguais.';
    }


    // Validação da data

    if ($data_viagem === '') {

        $erros[] =
            'Informe a data da viagem.';
    } else {

        $data_valida =
            DateTime::createFromFormat(
                'Y-m-d',
                $data_viagem
            );

        if (
            !$data_valida ||
            $data_valida->format('Y-m-d') !== $data_viagem
        ) {

            $erros[] =
                'Informe uma data de viagem válida.';
        }
    }


    // Validação da hora de partida

    if ($hora_partida === '') {

        $erros[] =
            'Informe o horário de partida.';
    } else {

        $hora_valida =
            DateTime::createFromFormat(
                'H:i',
                $hora_partida
            );

        if (
            !$hora_valida ||
            $hora_valida->format('H:i') !== $hora_partida
        ) {

            $erros[] =
                'Informe um horário de partida válido.';
        }
    }


    // Validação da hora de chegada

    if ($hora_chegada !== '') {

        $hora_chegada_valida =
            DateTime::createFromFormat(
                'H:i',
                $hora_chegada
            );

        if (
            !$hora_chegada_valida ||
            $hora_chegada_valida->format('H:i') !== $hora_chegada
        ) {

            $erros[] =
                'Informe um horário de chegada válido.';
        }
    }


    // A chegada não pode ser antes da partida

    if (
        $hora_partida !== '' &&
        $hora_chegada !== ''
    ) {

        if ($hora_chegada < $hora_partida) {

            $erros[] =
                'O horário de chegada não pode ser anterior ao horário de partida.';
        }
    }


    // Validação do status

    if (!array_key_exists($status, $status_opcoes)) {

        $erros[] =
            'Selecione um status válido.';
    }


    /*
     * Verifica se o mesmo trem já possui
     * outra viagem no mesmo dia.
     *
     * Esta verificação considera sobreposição
     * de horários quando existe horário de chegada.
     */

    if (
        $id_trem > 0 &&
        $data_viagem !== '' &&
        $hora_partida !== '' &&
        $hora_chegada !== ''
    ) {

        $sql_conflito = '
            SELECT id_rota
            FROM rotas
            WHERE id_trem = ?
              AND data_viagem = ?
              AND id_rota <> ?
              AND hora_partida < ?
              AND (
                    hora_chegada IS NULL
                    OR hora_chegada > ?
              )
            LIMIT 1
        ';

        $stmt = $conexao->prepare($sql_conflito);

        $stmt->bind_param(
            'isiss',
            $id_trem,
            $data_viagem,
            $id,
            $hora_chegada,
            $hora_partida
        );

        $stmt->execute();

        $conflito =
            $stmt->get_result()->num_rows > 0;

        $stmt->close();


        if ($conflito) {

            $erros[] =
                'Este trem já possui uma viagem com horário sobreposto nesta data.';
        }
    }


    // Se não houver erros, salva os dados

    if (count($erros) === 0) {

        // Converte campo vazio para NULL

        $hora_chegada_banco =
            $hora_chegada !== ''
            ? $hora_chegada
            : null;


        if ($id > 0) {

            $stmt = $conexao->prepare(
                'UPDATE rotas
                 SET id_trem = ?,
                     origem = ?,
                     destino = ?,
                     data_viagem = ?,
                     hora_partida = ?,
                     hora_chegada = ?,
                     status = ?
                 WHERE id_rota = ?'
            );

            $stmt->bind_param(
                'issssssi',
                $id_trem,
                $origem,
                $destino,
                $data_viagem,
                $hora_partida,
                $hora_chegada_banco,
                $status,
                $id
            );


            if ($stmt->execute()) {

                $_SESSION['mensagem'] =
                    'Rota atualizada com sucesso!';
            } else {

                $_SESSION['mensagem'] =
                    'Não foi possível realizar a atualização.';
            }
        } else {

            $stmt = $conexao->prepare(
                'INSERT INTO rotas
                (
                    id_trem,
                    origem,
                    destino,
                    data_viagem,
                    hora_partida,
                    hora_chegada,
                    status
                )
                VALUES (?, ?, ?, ?, ?, ?, ?)'
            );

            $stmt->bind_param(
                'issssss',
                $id_trem,
                $origem,
                $destino,
                $data_viagem,
                $hora_partida,
                $hora_chegada_banco,
                $status
            );


            if ($stmt->execute()) {

                $_SESSION['mensagem'] =
                    'Rota cadastrada com sucesso!';
            } else {

                $_SESSION['mensagem'] =
                    'Não foi possível realizar o cadastro.';
            }
        }


        $stmt->close();

        header('Location: rotas.php');

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
        <?= $id > 0 ? 'Editar rota' : 'Nova rota' ?>
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



    <div
        class="overlay"
        id="overlay">
    </div>



    <main>


        <h1>

            <?= $id > 0
                ? 'Editar rota'
                : 'Nova rota'
            ?>

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

                <label for="id_trem">

                    Trem

                </label>


                <select
                    id="id_trem"
                    name="id_trem"
                    required>

                    <option value="">

                        Selecione o trem

                    </option>


                    <?php foreach ($trens as $trem): ?>

                        <option
                            value="<?= (int) $trem['id_trem'] ?>"
                            <?= (int) $trem['id_trem'] === (int) $id_trem
                                ? 'selected'
                                : '' ?>>

                            <?= htmlspecialchars($trem['prefixo']) ?>

                            <?php if (!empty($trem['modelo'])): ?>

                                -
                                <?= htmlspecialchars($trem['modelo']) ?>

                            <?php endif; ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>



            <div class="campo">

                <label for="origem">

                    Origem

                </label>


                <input
                    type="text"
                    id="origem"
                    name="origem"
                    maxlength="80"
                    value="<?= htmlspecialchars($origem) ?>"
                    placeholder="Digite a origem da viagem"
                    required>

            </div>



            <div class="campo">

                <label for="destino">

                    Destino

                </label>


                <input
                    type="text"
                    id="destino"
                    name="destino"
                    maxlength="80"
                    value="<?= htmlspecialchars($destino) ?>"
                    placeholder="Digite o destino da viagem"
                    required>

            </div>



            <div class="campo">

                <label for="data_viagem">

                    Data da viagem

                </label>


                <input
                    type="date"
                    id="data_viagem"
                    name="data_viagem"
                    value="<?= htmlspecialchars($data_viagem) ?>"
                    required>

            </div>



            <div class="campo">

                <label for="hora_partida">

                    Hora de partida

                </label>


                <input
                    type="time"
                    id="hora_partida"
                    name="hora_partida"
                    value="<?= htmlspecialchars($hora_partida) ?>"
                    required>

            </div>



            <div class="campo">

                <label for="hora_chegada">

                    Hora de chegada

                </label>


                <input
                    type="time"
                    id="hora_chegada"
                    name="hora_chegada"
                    value="<?= htmlspecialchars($hora_chegada ?? '') ?>">

            </div>



            <div class="campo">

                <label for="status">

                    Status

                </label>


                <select
                    id="status"
                    name="status"
                    required>

                    <option value="">

                        Selecione o status

                    </option>


                    <?php foreach ($status_opcoes as $valor => $rotulo): ?>

                        <option
                            value="<?= htmlspecialchars($valor) ?>"
                            <?= $valor === $status
                                ? 'selected'
                                : '' ?>>

                            <?= htmlspecialchars($rotulo) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>



            <div class="acoes">


                <a
                    href="rotas.php"
                    class="botao botao-secundario">

                    Cancelar

                </a>



                <button
                    type="submit"
                    class="botao botao-primario">

                    <?= $id > 0
                        ? 'Atualizar'
                        : 'Cadastrar'
                    ?>

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

            menuBtn.classList.toggle(
                "active",
                aberto
            );

            overlay.classList.toggle(
                "active",
                aberto
            );

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
    </script>


</body>

</html>