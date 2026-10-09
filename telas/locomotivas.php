<?php

require 'proteger.php';
require 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_id'])) {
    $id = (int) $_POST['excluir_id'];

    $stmt = $conexao->prepare('DELETE FROM trem WHERE id_trem = ?');
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
        $_SESSION['mensagem'] = 'Trem excluído com sucesso.';

        header('Location: locomotivas.php');

        $stmt->close();

        exit;
    } else {
        $_SESSION['mensagem'] = 'Erro ao excluir o trem.';

        header('Location: locomotivas.php');

        $stmt->close();

        exit;
    }
}

$mensagem = $_SESSION['mensagem'] ?? '';
unset($_SESSION['mensagem']);

$resultado = $conexao->query('SELECT * FROM trem ORDER BY prefixo');

?>

<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>FerroHub - Trens</title>

    <link rel="stylesheet" href="../styles/locomotivas.css?v=<?= filemtime(__DIR__ . '/../styles/locomotivas.css') ?>">
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


    <div class="overlay" id="overlay"></div>

    <main class="dashboard">

        <div class="titulo">

            <h2>Locomotivas</h2>

            <a href="formulario.php" class="botao botao-primario">
                Novo trem
            </a>

        </div>


        <?php if ($mensagem !== ''): ?>

            <p class="aviso">
                <?= htmlspecialchars($mensagem) ?>
            </p>

        <?php endif; ?>


        <?php if ($resultado->num_rows === 0): ?>

            <p class="vazio">
                Nenhum trem cadastrado.
            </p>

        <?php else: ?>


            <table>

                <thead>

                    <tr>
                        <th>Prefixo</th>
                        <th>Modelo</th>
                        <th>Ano</th>
                        <th>Status</th>
                        <th colspan="2">Ações</th>
                    </tr>

                </thead>


                <tbody>

                    <?php while ($linha = $resultado->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($linha['prefixo']) ?>
                            </td>


                            <td>
                                <?= htmlspecialchars($linha['modelo']) ?>
                            </td>


                            <td>
                                <?= (int) $linha['ano_de_fabricacao'] ?>
                            </td>


                            <td>

                                <span
                                    class="etiqueta etiqueta-<?= htmlspecialchars($linha['status']) ?>">
                                    <?= htmlspecialchars($linha['status']) ?>
                                </span>

                            </td>


                            <td class="acoes">

                                <a
                                    href="formulario.php?id=<?= (int) $linha['id_trem'] ?>"
                                    class="botao botao-secundario">
                                    Editar
                                </a>


                                <form
                                    method="post"
                                    onsubmit="return confirm('Confirma a exclusão do trem?');">

                                    <input
                                        type="hidden"
                                        name="excluir_id"
                                        value="<?= (int) $linha['id_trem'] ?>">

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