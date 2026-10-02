<?php
require_once 'config.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$mensagem = '';
$erro = '';

if (isset($_GET['excluir'])) {
    $id_excluir = (int)$_GET['excluir'];
    $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
    $stmt->execute([$id_excluir]);
    header("Location: produtos.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $codigo = trim($_POST['codigo'] ?? '');
    $nome = trim($_POST['nome'] ?? '');
    $id_categoria = (int)($_POST['id_categoria'] ?? 0);
    $preco = (float)($_POST['preco'] ?? 0);
    $estoque_atual = (int)($_POST['estoque_atual'] ?? 0);
    $estoque_minimo = (int)($_POST['estoque_minimo'] ?? 0);

    if (empty($codigo) || empty($nome) || $id_categoria <= 0 || $preco < 0 || $estoque_atual < 0 || $estoque_minimo < 0) {
        $erro = "Por favor, preencha todos os campos corretamente com valores válidos!";
    } else {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE produtos SET codigo = ?, nome = ?, id_categoria = ?, preco = ?, estoque_atual = ?, estoque_minimo = ? WHERE id = ?");
            $stmt->execute([$codigo, $nome, $id_categoria, $preco, $estoque_atual, $estoque_minimo, $id]);
            $mensagem = "Insumo atualizado com sucesso!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO produtos (codigo, nome, id_categoria, preco, estoque_atual, estoque_minimo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$codigo, $nome, $id_categoria, $preco, $estoque_atual, $estoque_minimo]);
            $mensagem = "Insumo cadastrado com sucesso!";
        }
    }
}

$editar_item = null;
if (isset($_GET['editar'])) {
    $id_editar = (int)$_GET['editar'];
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->execute([$id_editar]);
    $editar_item = $stmt->fetch();
}

$busca = trim($_GET['busca'] ?? '');
if ($busca !== '') {
    $stmt = $pdo->prepare("SELECT p.*, c.nome AS categoria FROM produtos p LEFT JOIN categorias c ON p.id_categoria = c.id WHERE p.nome LIKE ? OR p.codigo LIKE ? ORDER BY p.codigo ASC");
    $stmt->execute(["%$busca%", "%$busca%"]);
} else {
    $stmt = $pdo->query("SELECT p.*, c.nome AS categoria FROM produtos p LEFT JOIN categorias c ON p.id_categoria = c.id ORDER BY p.codigo ASC");
}
$produtos = $stmt->fetchAll();
$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro e Consulta de Recursos</title>
    <link rel="stylesheet" href="estilo.css">
    <script>
        function validarFormulario() {
            var codigo = document.forms["formInsumo"]["codigo"].value;
            var nome = document.forms["formInsumo"]["nome"].value;
            if (codigo.trim() == "" || nome.trim() == "") {
                alert("Atenção: Preencha o Código e o Nome do insumo!");
                return false;
            }
            return true;
        }
    </script>
</head>
<body>
    <h2>Cadastro e Consulta de Recursos</h2>

    <p><a href="index.php">Voltar</a></p>

    <?php if ($mensagem): ?><p style="color: green; font-weight: bold;"><?= htmlspecialchars($mensagem) ?></p><?php endif; ?>
    <?php if ($erro): ?><p style="color: red; font-weight: bold;"><?= htmlspecialchars($erro) ?></p><?php endif; ?>

    <form method="GET" style="margin-bottom: 20px;">
        <input type="text" name="busca" placeholder="Buscar por código ou nome..." value="<?= htmlspecialchars($busca) ?>">
        <button type="submit" class="btn-simples">Buscar</button>
        <?php if ($busca): ?><a href="produtos.php">Limpar Busca</a><?php endif; ?>
    </form>

    <h3><?= $editar_item ? 'Editar Recurso' : 'Novo Recurso' ?></h3>
    <form name="formInsumo" method="POST" class="form-inline" onsubmit="return validarFormulario();">
        <input type="hidden" name="id" value="<?= $editar_item['id'] ?? 0 ?>">
        <input type="text" name="codigo" placeholder="Código (ex: MAT-001)" value="<?= htmlspecialchars($editar_item['codigo'] ?? '') ?>" required>
        <input type="text" name="nome" placeholder="Nome do Insumo" value="<?= htmlspecialchars($editar_item['nome'] ?? '') ?>" required>
        <select name="id_categoria" required>
            <option value="">Selecione Categoria...</option>
            <?php foreach ($categorias as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= (isset($editar_item) && $editar_item['id_categoria'] == $cat['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="number" step="0.01" name="preco" placeholder="Preço (R$)" value="<?= htmlspecialchars($editar_item['preco'] ?? '') ?>" required>
        <input type="number" name="estoque_atual" placeholder="Estoque Inicial" value="<?= htmlspecialchars($editar_item['estoque_atual'] ?? '') ?>" required>
        <input type="number" name="estoque_minimo" placeholder="Estoque Mínimo" value="<?= htmlspecialchars($editar_item['estoque_minimo'] ?? '') ?>" required>
        <button type="submit" class="btn-simples"><?= $editar_item ? 'Atualizar Insumo' : 'Salvar Insumo' ?></button>
        <?php if ($editar_item): ?><a href="produtos.php">Cancelar</a><?php endif; ?>
    </form>

    <h3>Lista de Recursos Cadastrados</h3>
    <table class="tabela-tradicional">
        <thead>
            <tr>
                <th>Código</th>
                <th>Nome</th>
                <th>Categoria</th>
                <th>Preço</th>
                <th>Estoque Atual</th>
                <th>Estoque Mínimo</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($produtos as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['codigo']) ?></td>
                    <td><?= htmlspecialchars($p['nome']) ?></td>
                    <td><?= htmlspecialchars($p['categoria'] ?? '-') ?></td>
                    <td>R$ <?= number_format($p['preco'], 2, ',', '.') ?></td>
                    <td><?= $p['estoque_atual'] ?></td>
                    <td><?= $p['estoque_minimo'] ?></td>
                    <td>
                        <a href="produtos.php?editar=<?= $p['id'] ?>">Editar</a> | 
                        <a href="produtos.php?excluir=<?= $p['id'] ?>" onclick="return confirm('Deseja realmente excluir este item?');">Excluir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>