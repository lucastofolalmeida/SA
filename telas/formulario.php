<?php

require 'proteger.php';
require 'conexao.php';

$id = (int) ($_GET['id'] ?? 0);
$prefixo = '';
$modelo = '';
$ano_de_fabricacao = '';
$status = 'ativo';
$status_opcoes = [
    'ativo' => 'Ativo',
    'manutencao' => 'Em manutenção',
    'inativo' => 'Inativo'
];
$erros = [];

if ($id > 0 && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conexao->prepare('SELECT * FROM trem WHERE id_trem = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $trem = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$trem) {
        $_SESSION['mensagem'] = "Trem não encontrado.";
        header("Location: login.php");
        exit;
    }

    $prefixo = $trem['prefixo'];
    $modelo = $trem['modelo'];
    $ano_de_fabricacao = $trem['ano_de_fabricacao'];
    $status = $trem['status'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) $_POST['id'];
    $prefixo = trim($_POST['prefixo']);
    $modelo = trim($_POST['modelo']);
    $ano_de_fabricacao = trim($_POST['ano_de_fabricacao']);
    $status = $_POST['status'];

    if ($prefixo === '') {
        $erros[] = 'Informe o prefixo do trem.';
    }

    if ($modelo === '') {
        $erros[] = 'Informe o modelo do trem.';
    }

    if (!is_numeric($ano_de_fabricacao) || $ano_de_fabricacao < 1900 || $ano_de_fabricacao > 2100) {
        $erros[] = 'Informe um ano de fabricação entre 1900 e 2100.';
    }

    if (!isset($status_opcoes[$status])) {
        $erros[] = 'Selecione um status válido.';
    }

    if (count($erros) === 0) {
        $ano = (int) $ano_de_fabricacao;

        if ($id > 0) {
            $stmt = $conexao->prepare('UPDATE trem SET prefixo = ?, modelo = ?, ano_de_fabricacao = ?, status = ? WHERE id_trem = ?');
            $stmt->bind_param('ssisi', $prefixo, $modelo, $ano, $status, $id);

            if ($stmt->execute()) {
                $_SESSION['mensagem'] = 'Trem atualizado com sucesso!';
            } else {
                $_SESSION['mensagem'] = 'Não foi possível realizar a atualização.';
            }
        } else {
            $stmt = $conexao->prepare('INSERT INTO trem (prefixo, modelo, ano_de_fabricacao, status) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssis', $prefixo, $modelo, $ano_de_fabricacao, $status);

            if ($stmt->execute()) {
                $_SESSION['mensagem'] = 'Trem cadastrado com sucesso!';
            } else {
                $_SESSION['mensagem'] = 'Não foi possível realizar o cadastro.';
            }
        }

        $stmt->close();
        header('Location: locomotivas.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id > 0 ? 'Editar trem' : 'Novo trem' ?></title>
    <link rel="stylesheet" href="../styles/formulario.css">
</head>

<body>
    <header class="topbar">
        <button class="menu-btn" id="menuBtn" aria-label="Abrir menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <h1>FerroHub</h1>

        <a class="logout-btn" href="logout.php">Sair</a>
    </header>

    <aside class="sidebar" id="sidebar">
        <nav>
            <a href="locomotivas.php" class="side-link">
                <img src="../assets/locomotiva.png" alt="" />
                <span>Locomotivas</span>
            </a>

            <a href="relatorios.php" class="side-link">
                <img src="../assets/relatorio.webp" alt="" />
                <span>Relatórios</span>
            </a>

            <a href="sensores.php" class="side-link">
                <img src="../assets/sensores.webp" alt="" />
                <span>Sensores</span>
            </a>

            <a href="usuarios.php" class="side-link">
                <img src="../assets/usuarios.png" alt="" />
                <span>Usuários</span>
            </a>
        </nav>
    </aside>

     <div class="overlay" id="overlay"></div>

    <main>
        <h1><?= $id > 0 ? 'Editar trem' : 'Novo trem' ?></h1>

        <?php
        if (count($erros) > 0):
        ?>
            <div class="aviso aviso-erro">
                <ul>
                    <?php
                    foreach ($erros as $item):
                    ?>
                        <li><?= htmlspecialchars($item) ?></li>
                    <?php
                    endforeach;
                    ?>
                </ul>
            </div>
        <?php
        endif;
        ?>
    </main>

    <form method="post">
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="linha">
            <div class="campo">
                <label for="prefixo">Prefixo</label>
                <input type="text" id="prefixo" name="prefixo" maxlength="20" value="<?= htmlspecialchars($prefixo) ?>">
            </div>

            <div class="campo">
                <label for="ano_de_fabricacao">Ano de fabricação</label>
                <input type="number" id="ano_de_fabricacao" name="ano_de_fabricacao" min="1900" max="2100" value="<?= htmlspecialchars($ano_de_fabricacao) ?>">
            </div>
        </div>

        <div class="campo">
            <label for="modelo">Modelo</label>
            <input type="text" id="modelo" name="modelo" maxlength="80" value="<?= htmlspecialchars($modelo) ?>">
        </div>

        <div class="linha">

            <div class="campo">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <?php
                    foreach ($status_opcoes as $chave => $rotulo):
                    ?>
                        <option value="<?= $chave ?>" <?= $chave === $status ? 'selected' : '' ?>>
                            <?= $rotulo ?>
                        </option>
                    <?php
                    endforeach;
                    ?>
                </select>
            </div>
        </div>

        <div class="acoes">
            <button type="submit" class="botao botao-primario"><?= $id > 0 ? 'Atualizar' : 'Cadastrar' ?></button>
            <a href="locomotivas.php" class="botao botao-secundario">Cancelar</a>
        </div>
    </form>

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