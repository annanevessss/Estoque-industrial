<?php
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$mensagem = '';
$alerta_minimo = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_produto = (int)($_POST['id_produto'] ?? 0);
    $tipo = $_POST['tipo'] ?? '';
    $quantidade = (int)($_POST['quantidade'] ?? 0);
    $data_movimentacao = $_POST['data_movimentacao'] ?? '';
    $id_usuario = $_SESSION['user_id'];

    if ($id_produto <= 0 || !in_array($tipo, ['entrada', 'saida']) || $quantidade <= 0 || empty($data_movimentacao)) {
        $erro = "Preencha todos os campos da movimentação corretamente.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
        $stmt->execute([$id_produto]);
        $produto = $stmt->fetch();

        if (!$produto) {
            $erro = "Produto não encontrado.";
        } else {
            $novo_estoque = $produto['estoque_atual'];

            if ($tipo === 'entrada') {
                $novo_estoque += $quantidade;
            } else {
                if ($quantidade > $novo_estoque) {
                    $erro = "Quantidade de saída indisponível em estoque! Saldo atual: " . $novo_estoque;
                } else {
                    $novo_estoque -= $quantidade;
                }
            }

            if (empty($erro)) {
                $stmtUpdate = $pdo->prepare("UPDATE produtos SET estoque_atual = ? WHERE id = ?");
                $stmtUpdate->execute([$novo_estoque, $id_produto]);

                $stmtMov = $pdo->prepare("INSERT INTO movimentacoes (id_produto, id_usuario, tipo, quantidade, data_movimentacao) VALUES (?, ?, ?, ?, ?)");
                $stmtMov->execute([$id_produto, $id_usuario, $tipo, $quantidade, $data_movimentacao]);

                $mensagem = "Movimentação registrada com sucesso!";

                if ($novo_estoque <= $produto['estoque_minimo']) {
                    $alerta_minimo = "ATENÇÃO: O produto '" . htmlspecialchars($produto['nome']) . "' atingiu o limite de estoque mínimo! Saldo atual: " . $novo_estoque . " (Mínimo: " . $produto['estoque_minimo'] . ").";
                }
            }
        }
    }
}

// Produtos em Ordem ALFABÉTICA
$produtos_select = $pdo->query("SELECT id, codigo, nome, estoque_atual FROM produtos ORDER BY nome ASC")->fetchAll();

// Histórico de Movimentações
$stmtHist = $pdo->query("
    SELECT m.*, p.codigo AS produto_codigo, p.nome AS produto_nome, u.nome AS usuario_nome 
    FROM movimentacoes m 
    JOIN produtos p ON m.id_produto = p.id 
    JOIN usuarios u ON m.id_usuario = u.id 
    ORDER BY m.data_movimentacao DESC
");
$historico = $stmtHist->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Estoque</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-success mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">← Voltar ao Painel Principal</a>
        <span class="navbar-text text-white">Gestão de Estoque</span>
    </div>
</nav>

<div class="container">
    <?php if ($mensagem): ?><div class="alert alert-success"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?>
    <?php if ($erro): ?><div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
    <?php if ($alerta_minimo): ?>
        <div class="alert alert-warning border-warning shadow-sm">
            <h5>⚠️ Alerta de Estoque Mínimo!</h5>
            <p class="mb-0"><?= htmlspecialchars($alerta_minimo) ?></p>
        </div>
        <script>
            alert("<?= addslashes($alerta_minimo) ?>");
        </script>
    <?php endif; ?>

    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Registrar Entrada / Saída de Material</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="estoque.php">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Selecione o Insumo (Ordem Alfabética)</label>
                        <select name="id_produto" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($produtos_select as $p): ?>
                                <option value="<?= $p['id'] ?>">
                                    <?= htmlspecialchars($p['nome']) ?> (Cód: <?= htmlspecialchars($p['codigo']) ?> - Saldo: <?= $p['estoque_atual'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tipo Operação</label>
                        <select name="tipo" class="form-select" required>
                            <option value="entrada">Entrada (+)</option>
                            <option value="saida">Saída (-)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Quantidade</label>
                        <input type="number" name="quantidade" class="form-control" min="1" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Data e Hora da Movimentação</label>
                        <input type="datetime-local" name="data_movimentacao" class="form-control" required value="<?= date('Y-m-d\TH:i') ?>">
                    </div>
                    <div class="col-md-12 text-end">
                        <button type="submit" class="btn btn-success btn-lg">Confirmar Movimentação</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Histórico de Movimentações Auditável</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Data / Hora</th>
                            <th>Código</th>
                            <th>Insumo</th>
                            <th>Tipo</th>
                            <th>Qtd Movimentada</th>
                            <th>Operador Responsável</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($historico)): ?>
                            <tr><td colspan="6" class="text-center p-3 text-muted">Nenhuma movimentação registrada.</td></tr>
                        <?php else: ?>
                            <?php foreach ($historico as $h): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($h['data_movimentacao'])) ?></td>
                                    <td><strong><?= htmlspecialchars($h['produto_codigo']) ?></strong></td>
                                    <td><?= htmlspecialchars($h['produto_nome']) ?></td>
                                    <td>
                                        <?php if ($h['tipo'] === 'entrada'): ?>
                                            <span class="badge bg-success">Entrada</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Saída</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= $h['quantidade'] ?></strong></td>
                                    <td><?= htmlspecialchars($h['usuario_nome']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>