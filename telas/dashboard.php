<?php

require 'conexao.php';

$id = $_GET['id'];

$stmt = $pdo->prepare('SELECT id_trem, status FROM trem WHERE id_trem = :id');
$stmt->execute(['id' => $id]);

$trem = $stmt->fetch(); //pega os dados do trem retornado
?>

<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard</title>
    <link rel="stylesheet" href="../styles/inicial.css" />
  </head>
  <body>
    <div> <!-- Contagem de trens por status-->
        <h1>Detalhes</h1>
    </div>
  </body>
</html