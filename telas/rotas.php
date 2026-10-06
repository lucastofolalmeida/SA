<?php

require 'proteger.php';
require 'conexao.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_id'])) {

    $id = (int) $_POST['excluir_id'];

    $stmt = $conexao->prepare('DELETE FROM rotas WHERE id_rota = ?');
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {

        $_SESSION['mensagem'] = 'Rota excluída com sucesso.';

        header('Location: rotas.php');

        $stmt->close();

        exit;
    } else {

        $_SESSION['mensagem'] = 'Erro ao excluir a rota.';

        header('Location: rotas.php');

        $stmt->close();

        exit;
    }
}


$mensagem = $_SESSION['mensagem'] ?? '';
unset($_SESSION['mensagem']);


// Filtros

$status = $_GET['status'] ?? '';
$id_trem = isset($_GET['id_trem']) ? (int) $_GET['id_trem'] : 0;

$data_inicio = $_GET['data_inicio'] ?? '';
$data_fim = $_GET['data_fim'] ?? '';

$busca = trim($_GET['busca'] ?? '');


// Condições da consulta

$condicoes = [];
$valores = [];
$tipos = '';


if ($status !== '') {

    $condicoes[] = 'rotas.status = ?';

    $valores[] = $status;

    $tipos .= 's';
}


if ($id_trem > 0) {

    $condicoes[] = 'rotas.id_trem = ?';

    $valores[] = $id_trem;

    $tipos .= 'i';
}


if ($data_inicio !== '' && $data_fim !== '') {

    $condicoes[] = 'rotas.data_viagem BETWEEN ? AND ?';

    $valores[] = $data_inicio;
    $valores[] = $data_fim;

    $tipos .= 'ss';
}


if ($busca !== '') {

    $condicoes[] = '(rotas.origem LIKE ? OR rotas.destino LIKE ?)';

    $termo = '%' . $busca . '%';

    $valores[] = $termo;
    $valores[] = $termo;

    $tipos .= 'ss';
}


// Montagem da consulta

$sql = '
  SELECT
    rotas.id_rota,
    rotas.id_trem,
    rotas.origem,
    rotas.destino,
    rotas.data_viagem,
    rotas.hora_partida,
    rotas.hora_chegada,
    rotas.status,
    trem.prefixo,
    trem.modelo
  FROM rotas
  INNER JOIN trem ON trem.id_trem = rotas.id_trem
';


if (!empty($condicoes)) {

    $sql .= ' WHERE ' . implode(' AND ', $condicoes);
}


$sql .= '
  ORDER BY rotas.data_viagem DESC, rotas.hora_partida
';


$stmt = $conexao->prepare($sql);


if (!empty($valores)) {

    $stmt->bind_param($tipos, ...$valores);
}


$stmt->execute();

$resultado = $stmt->get_result();


// Lista de trens para o filtro

$resultado_trem = $conexao->query('
  SELECT id_trem, prefixo, modelo
  FROM trem
  ORDER BY prefixo
');


?>

<!doctype html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8" />

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0" />

    <title>FerroHub - Rotas</title>

    <link
        rel="stylesheet"
        href="../styles/locomotivas.css?v=<?= filemtime(__DIR__ . '/../styles/locomotivas.css') ?>">

</head>


<body>


    <header class="topbar">

        <button
            class="menu-btn"
            id="menuBtn"
            aria-label="Abrir menu">

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



    <main class="dashboard">


        <div class="titulo">

            <h2>Rotas</h2>


            <a
                href="formulario_rota.php"
                class="botao botao-primario">
                Nova rota
            </a>

        </div>



        <?php if ($mensagem !== ''): ?>

            <p class="aviso">

                <?= htmlspecialchars($mensagem) ?>

            </p>

        <?php endif; ?>



        <form
            method="get"
            style="
        background: #cceaff;
        padding: 18px;
        border-radius: 18px;
        margin-bottom: 20px;
      ">

            <div
                style="
          display: flex;
          flex-wrap: wrap;
          gap: 12px;
          align-items: end;
        ">


                <div>

                    <label
                        for="status"
                        style="font-weight: bold;">
                        Status
                    </label>

                    <br>

                    <select
                        name="status"
                        id="status"
                        style="
              padding: 9px;
              border-radius: 10px;
              border: 1px solid #003459;
              margin-top: 5px;
            ">

                        <option value="">Todos</option>

                        <option
                            value="programada"
                            <?= $status === 'programada' ? 'selected' : '' ?>>
                            Programada
                        </option>

                        <option
                            value="em andamento"
                            <?= $status === 'em andamento' ? 'selected' : '' ?>>
                            Em andamento
                        </option>

                        <option
                            value="concluída"
                            <?= $status === 'concluída' ? 'selected' : '' ?>>
                            Concluída
                        </option>

                        <option
                            value="cancelada"
                            <?= $status === 'cancelada' ? 'selected' : '' ?>>
                            Cancelada
                        </option>

                    </select>

                </div>



                <div>

                    <label
                        for="id_trem"
                        style="font-weight: bold;">
                        Trem
                    </label>

                    <br>

                    <select
                        name="id_trem"
                        id="id_trem"
                        style="
              padding: 9px;
              border-radius: 10px;
              border: 1px solid #003459;
              margin-top: 5px;
            ">

                        <option value="">Todos</option>


                        <?php while ($trem = $resultado_trem->fetch_assoc()): ?>

                            <option
                                value="<?= (int) $trem['id_trem'] ?>"
                                <?= $id_trem === (int) $trem['id_trem'] ? 'selected' : '' ?>>

                                <?= htmlspecialchars($trem['prefixo']) ?>

                                <?php if (!empty($trem['modelo'])): ?>

                                    - <?= htmlspecialchars($trem['modelo']) ?>

                                <?php endif; ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>



                <div>

                    <label
                        for="data_inicio"
                        style="font-weight: bold;">
                        Data inicial
                    </label>

                    <br>

                    <input
                        type="date"
                        name="data_inicio"
                        id="data_inicio"
                        value="<?= htmlspecialchars($data_inicio) ?>"
                        style="
              padding: 8px;
              border-radius: 10px;
              border: 1px solid #003459;
              margin-top: 5px;
            ">

                </div>



                <div>

                    <label
                        for="data_fim"
                        style="font-weight: bold;">
                        Data final
                    </label>

                    <br>

                    <input
                        type="date"
                        name="data_fim"
                        id="data_fim"
                        value="<?= htmlspecialchars($data_fim) ?>"
                        style="
              padding: 8px;
              border-radius: 10px;
              border: 1px solid #003459;
              margin-top: 5px;
            ">

                </div>



                <div>

                    <label
                        for="busca"
                        style="font-weight: bold;">
                        Rota
                    </label>

                    <br>

                    <input
                        type="text"
                        name="busca"
                        id="busca"
                        placeholder="Origem ou destino"
                        value="<?= htmlspecialchars($busca) ?>"
                        style="
              padding: 9px;
              border-radius: 10px;
              border: 1px solid #003459;
              margin-top: 5px;
            ">

                </div>



                <button
                    type="submit"
                    class="botao botao-primario">
                    Filtrar
                </button>


                <a
                    href="rotas.php"
                    class="botao botao-secundario">
                    Limpar
                </a>


            </div>

        </form>



        <?php if ($resultado->num_rows === 0): ?>

            <p class="vazio">

                Nenhuma rota encontrada.

            </p>

        <?php else: ?>


            <table>


                <thead>

                    <tr>

                        <th>ID</th>

                        <th>Trem</th>

                        <th>Origem</th>

                        <th>Destino</th>

                        <th>Data da viagem</th>

                        <th>Partida</th>

                        <th>Chegada</th>

                        <th>Status</th>

                        <th colspan="2">Ações</th>

                    </tr>

                </thead>



                <tbody>


                    <?php while ($linha = $resultado->fetch_assoc()): ?>

                        <tr>


                            <td>

                                <?= (int) $linha['id_rota'] ?>

                            </td>



                            <td>

                                <?= htmlspecialchars($linha['prefixo']) ?>

                                <?php if (!empty($linha['modelo'])): ?>

                                    <br>

                                    <small>
                                        <?= htmlspecialchars($linha['modelo']) ?>
                                    </small>

                                <?php endif; ?>

                            </td>



                            <td>

                                <?= htmlspecialchars($linha['origem']) ?>

                            </td>



                            <td>
                                <?= htmlspecialchars($linha['destino']) ?>
                            </td>



                            <td>
                                <?= htmlspecialchars($linha['data_viagem']) ?>
                            </td>



                            <td>
                                <?= htmlspecialchars($linha['hora_partida']) ?>
                            </td>



                            <td>
                                <?= htmlspecialchars($linha['hora_chegada'] ?? '—') ?>
                            </td>



                            <td>

                                <?php

                                $classe_status = 'etiqueta-inativo';

                                $status_exibicao = $linha['status'];

                                if ($linha['status'] === 'programada') {

                                    $classe_status = 'etiqueta-ativo';
                                } elseif ($linha['status'] === 'em andamento') {

                                    $classe_status = 'etiqueta-manutencao';
                                } elseif ($linha['status'] === 'concluída') {

                                    $classe_status = 'etiqueta-ativo';
                                } elseif ($linha['status'] === 'cancelada') {

                                    $classe_status = 'etiqueta-inativo';
                                }

                                ?>

                                <span class="etiqueta <?= $classe_status ?>">

                                    <?= htmlspecialchars($status_exibicao) ?>

                                </span>

                            </td>



                            <td class="acoes">


                                <a
                                    href="formulario_rota.php?id=<?= (int) $linha['id_rota'] ?>"
                                    class="botao botao-secundario">
                                    Editar
                                </a>



                                <form
                                    method="post"
                                    onsubmit="return confirm('Confirma a exclusão da rota?');">

                                    <input
                                        type="hidden"
                                        name="excluir_id"
                                        value="<?= (int) $linha['id_rota'] ?>">


                                    <button
                                        type="submit"
                                        class="botao botao-perigo">
                                        Excluir
                                    </button>

                                </form>


                            </td>


                        </tr>

                    <?php endwhile; ?>


                </tbody>

            </table>


        <?php endif; ?>


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