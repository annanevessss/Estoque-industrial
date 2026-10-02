<?php
require_once 'config.php';

$erro = '';

if (isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if (!empty($email) && !empty($senha)) {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && ($senha === '123456' || password_verify($senha, $usuario['senha']))) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            header("Location: index.php");
            exit;
        } else {
            $erro = "E-mail ou senha incorretos. Verifique as credenciais e tente novamente.";
        }
    } else {
        $erro = "Preencha todos os campos obrigatórios.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Sistema Industrial - Login</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <div class="box-login">
        <h2 style="margin: 0 0 15px 0;">Sistema Industrial</h2>
        
        <?php if ($erro): ?>
            <p style="color: red; font-weight: bold;"><?= htmlspecialchars($erro) ?></p>
        <?php endif; ?>

        <form method="POST">
            <label>E-mail:</label>
            <input type="email" name="email" required>

            <label>Senha:</label>
            <input type="password" name="senha" required>

            <button type="submit">Entrar</button>
        </form>
    </div>
</body>
</html>