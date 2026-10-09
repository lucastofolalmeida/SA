<?php

require 'proteger.php';
require 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_id'])) {
  $id = (int) $_POST['excluir_id'];

  $stmt = $conexao->prepare('DELETE FROM sensor WHERE id_sensor = ?');
  $stmt->bind_param('i', $id);

  if ($stmt->execute()) {
    $_SESSION['mensagem'] = 'Sensor excluído com sucesso.';

    header('Location: sensores.php');

    $stmt->close();

    exit;
  } else {
    $_SESSION['mensagem'] = 'Erro ao excluir o sensor.';

    header('Location: sensores.php');

    $stmt->close();

    exit;
  }
}

$mensagem = $_SESSION['mensagem'] ?? '';
unset($_SESSION['mensagem']);

$resultado = $conexao->query('
    SELECT 
        sensor.id_sensor,
        sensor.tipo,
        sensor.ultima_leitura,
        sensor.local,
        trem.prefixo AS locomotiva
    FROM sensor
    LEFT JOIN trem ON sensor.id_trem = trem.id_trem
    ORDER BY sensor.id_sensor
');


?>

<!doctype html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <title>FerroHub - Sensores</title>

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

      <h2>Sensores</h2>

      <a href="formulario_sensor.php" class="botao botao-primario">
        Novo sensor
      </a>

    </div>


    <?php if ($mensagem !== ''): ?>

      <p class="aviso">
        <?= htmlspecialchars($mensagem) ?>
      </p>

    <?php endif; ?>


    <?php if ($resultado->num_rows === 0): ?>

      <p class="vazio">
        Nenhum sensor cadastrado.
      </p>

    <?php else: ?>


      <table>

        <thead>

          <tr>
            <th>ID</th>
            <th>Tipo</th>
            <th>Última leitura</th>
            <th>Local</th>
            <th>Locomotiva associada</th>
            <th colspan="2">Ações</th>
          </tr>

        </thead>


        <tbody>

          <?php while ($linha = $resultado->fetch_assoc()): ?>

            <tr>

              <td>
                <?= (int) $linha['id_sensor'] ?>
              </td>


              <td>
                <?= htmlspecialchars($linha['tipo']) ?>
              </td>


              <td>
                <?= htmlspecialchars($linha['ultima_leitura']) ?>
              </td>


              <td>
                <?= htmlspecialchars($linha['local']) ?>
              </td>
              
              <td>
                <?= htmlspecialchars($linha['locomotiva'] ?? 'Não associada') ?>
              </td>


              <td class="acoes">

                <a
                  href="formulario_sensor.php?id=<?= (int) $linha['id_sensor'] ?>"
                  class="botao botao-secundario">
                  Editar
                </a>


                <form
                  method="post"
                  onsubmit="return confirm('Confirma a exclusão do sensor?');">

                  <input
                    type="hidden"
                    name="excluir_id"
                    value="<?= (int) $linha['id_sensor'] ?>">

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