<?php
require_once 'config.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header("Location: login.php");
    exit;
}

$alertas_estoque = $pdo->query("SELECT COUNT(*) FROM produtos WHERE estoque_atual <= estoque_minimo")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel de Gestão</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <h1>SISTEMA DE CONTROLE DE ESTOQUE INDUSTRIAL</h1>
    
    <p>Utilizador: <strong><?= htmlspecialchars($_SESSION['usuario_nome']) ?></strong> | <a href="index.php?action=logout" class="link-sair">Sair</a></p>

    <h2>Painel de Gestão</h2>

    <div style="margin-bottom: 20px;">
        <p><a href="produtos.php" class="btn-simples">Cadastro de Produto/Insumo</a></p>
        <p><a href="estoque.php" class="btn-simples">Gestão de Estoque</a></p>
    </div>

    <?php if ($alertas_estoque > 0): ?>
        <div class="alerta-cinza">
            ATENÇÃO: Existem <?= $alertas_estoque ?> recursos com estoque abaixo ou igual ao limite mínimo!
        </div>
    <?php endif; ?>
</body>
</html>