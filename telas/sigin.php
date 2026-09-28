<?php
require 'conexao.php';

$login = '';
$email = '';
$erros = [];
$sucesso = '';
$cargo = '';
$status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $email = $_POST['email'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $confirmacao = $_POST['confirmarSenha'] ?? '';

    if ($login === '') {
        $erros[] = 'Informe o login.';
    } elseif (mb_strlen($login) > 80) {
        $erros[] = 'O login deve ter no máximo 80 caracteres.';
    }

    if ($email === '') {
      $erros[] = 'Informe o email';
    }

    if ($senha === '') {
        $erros[] = 'Informe a senha.';
    } elseif (strlen($senha) < 6) {
        $erros[] = 'A senha deve ter pelo menos 6 caracteres.';
    }

    if ($senha !== $confirmacao) {
        $erros[] = 'A confirmação da senha não confere.';
    }

    if (count($erros) === 0) {
        $stmt = $conexao->prepare('SELECT id FROM usuario WHERE login = ? LIMIT 1');
        $stmt->bind_param('s', $login);
        $stmt->execute();
        $existe = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existe) {
            $erros[] = 'Este login já está cadastrado.';
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);

            $stmt = $conexao->prepare('INSERT INTO usuario (login, senha) VALUES (?, ?)');
            $stmt->bind_param('ss', $login, $hash);

            if ($stmt->execute()) {
                $sucesso = 'Usuário cadastrado com sucesso.';
                $login = '';
            } else {
                $erros[] = 'Não foi possível cadastrar o usuário.';
            }

            $stmt->close();
        }
    }
}
?>

<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sigin</title>

    <link rel="stylesheet" href="../styles/sigin.css" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>

  </head>

  <body>
    <main class="container">
      <h1>Create your account:</h1>

      <form id="form-cadastro"> <!-- Nome -->
        <div class="input-box">
          <i class="fa-regular fa-user"></i>
          <input type="text" id="login" placeholder="Name" />
        </div>

        <div class="input-box"> <!-- Email -->
          <i class="fa-regular fa-envelope"></i>
          <input type="email" id="email" placeholder="Email" />
        </div>

        <div class="input-box senha-box"> <!-- Senha -->
          <i class="fa-solid fa-lock"></i>

          <input type="password" id="senha" placeholder="Password" />

          <i class="fa-regular fa-eye olho" id="olhoSenha"></i>
        </div>

        <div class="input-box senha-box"> <!-- Confirmar senha -->
          <i class="fa-solid fa-lock"></i>

          <input
            type="password"
            id="confirmarSenha"
            placeholder="Confirm Password"
          />

          <i class="fa-regular fa-eye olho" id="olhoConfirmar"></i>
        </div>

        <button type="submit">Create account</button>

        <p id="mensagem"></p>
        <p id="validacao"></p>
      </form>

      <div class="login">
        <p>
          Já tem uma conta?
          <a href="login.php">Faça <em>Log In</em></a>
        </p>
      </div>
    </main>

    <script>
      const form = document.getElementById("form-cadastro");
      const mensagem = document.getElementById("mensagem");
      const validacao = document.getElementById("validacao");

      const olhoSenha = document.getElementById("olhoSenha");
      const olhoConfirmar = document.getElementById("olhoConfirmar");

      const senha = document.getElementById("senha");
      const confirmarSenha = document.getElementById("confirmarSenha");

      olhoSenha.addEventListener("click", () => {
        senha.type = senha.type === "password" ? "text" : "password";
      });

      olhoConfirmar.addEventListener("click", () => {
        confirmarSenha.type =
          confirmarSenha.type === "password" ? "text" : "password";
      });

      form.addEventListener("submit", function (event) {

        const nome = document.getElementById("nome").value.trim();
        const email = document.getElementById("email").value.trim();
        const senhaValor = senha.value.trim();
        const confirmarValor = confirmarSenha.value.trim();

        mensagem.textContent = "";
        validacao.textContent = "";

        if (nome === "") {
          mensagem.textContent = "Preencha o primeiro nome.";
          return;
        }

        if (email === "") {
          mensagem.textContent = "Preencha o e-mail.";
          return;
        }

        if (!email.includes("@")) {
          mensagem.textContent = "Digite um e-mail válido.";
          return;
        }

        if (senhaValor.length < 6) {
          mensagem.textContent = "A senha deve ter pelo menos 6 caracteres.";
          return;
        }

        if (confirmarValor === "") {
          mensagem.textContent = "Confirme sua senha.";
          return;
        }

        if (senhaValor !== confirmarValor) {
          mensagem.textContent = "As senhas não coincidem.";
          return;
        }

        validacao.textContent = "Formulário validado com sucesso.";
        
      });
    </script>
  </body>
</html>
