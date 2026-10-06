<?php

$servidor = 'localhost';
$usuario = 'root';
$senha = '';
$banco = 'db_trem';

$conexao = new mysqli($servidor, $usuario, $senha, $banco);

if ($conexao->connect_error) {
    die('Falha na conexão: ' . $conexao->connect_error);
}

$conexao->set_charset('utf8mb4');

$resultado = $conexao->query("SELECT * FROM usuario");

if ($resultado->num_rows === 0) {
    $nome = "admin";
    $email = "admin@admin.com";
    $senha = "admin";
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $cargo = "admin";
    $status = "ativo";

    $comando = $conexao->prepare("INSERT INTO usuario (login, email, senha, cargo, status) VALUES (?, ?, ?, ?, ?)");
    $comando->bind_param("sssss", $login, $email, $hash, $cargo, $status);
    $comando->execute();
}