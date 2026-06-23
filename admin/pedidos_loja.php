<?php
header('Content-Type: text/html; charset=utf-8');
include_once('../config/db.php');
require_once('../include/admin_middleware.php');
verificarSuperAdmin();

$sucesso = $erro = '';

// Atualizar status do pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $validos = ['aguardando','confirmado','pronto','cancelado'];

    if ($id && in_array($status, $validos)) {
        // Se cancelar, restaura estoque
        if ($status === 'cancelado') {
            $old = $conn->query("SELECT status FROM pedidos WHERE id=$id")->fetch_assoc();
            if ($old && $old['status'] !== 'cancelado') {
                $itens = $conn->query("SELECT produto_id, quantidade FROM pedido_itens WHERE pedido_id=$id");
                while ($item = $itens->fetch_assoc()) {
                    $conn->query("UPDATE produtos SET estoque = estoque + {$item['quantidade']} WHERE id={$item['produto_id']}");
                }
            }
        }
        $conn->query("UPDATE pedidos SET status='$status' WHERE id=$id");
        $sucesso = 'Status atualizado!';
    }
}

// Buscar pedidos
$pedidos = [];
$res = $conn->query("SELECT * FROM pedidos ORDER BY FIELD(status,'aguardando','confirmado','pronto','cancelado'), criado_em DESC");
if ($res) while ($r = $res->fetch_assoc()) $pedidos[] = $r;

// Buscar itens de um pedido para modal
$detalhes = null;
if (isset($_GET['ver'])) {
    $ver_id  = (int)$_GET['ver'];
    $detalhes['pedido'] = $conn->query("SELECT * FROM pedidos WHERE id=$ver_id")->fetch_assoc();
    $detalhes['itens']  = [];
    $res_it = $conn->query("SELECT * FROM pedido_itens WHERE pedido_id=$ver_id");
    if ($res_it) while ($r = $res_it->fetch_assoc()) $detalhes['itens'][] = $r;
}

$status_labels = [
    'aguardando' => ['cor'=>'warning','txt'=>'Aguardando'],
    'confirmado' => ['cor'=>'primary','txt'=>'Confirmado'],
    'pronto'     => ['cor'=>'success','txt'=>'Pronto p/ Retirada'],
    'cancelado'  => ['cor'=>'danger', 'txt'=>'Cancelado'],
];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pedidos da Loja - Admin</title>
<link href="../assets/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
body { background:#f8f9fa; }
.admin-header {
    background:linear-gradient(135deg,#0a0a0a 0%,#052e16 55%,#15803d 100%);
    color:#fff; padding:1.5rem 0; margin-bottom:2rem;
    box-shadow:0 4px 16px rgba(10,10,10,0.4);
}
.card-pedido { background:#fff; border-radius:12px; padding:1rem 1.25rem; margin-bottom:.75rem; box-shadow:0 2px 8px rgba(0,0,0,0.07); border-left:4px solid #e2e8f0; }
.card-pedido.aguardando { border-left-color:#f59e0b; }
.card-pedido.confirmado { border-left-color:#3b82f6; }
.card-pedido.pronto     { border-left-color:#22c55e; }
.card-pedido.cancelado  { border-left-color:#ef4444; opacity:.7; }
</style>
</head>
<body>
<div class="admin-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-8">
                <h1><i class="fas fa-shopping-bag"></i> Pedidos da Loja</h1>
                <p>Gerencie os pedidos dos clientes</p>
            </div>
            <div class="col-4 text-end">
                <a href="index.php" class="btn btn-light"><i class="fas fa-arrow-left"></i> Voltar</a>
            </div>
        </div>
    </div>
</div>

<div class="container">
<?php if ($sucesso): ?>
<div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($sucesso) ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<?php if (empty($pedidos)): ?>
<div class="text-center py-5 text-muted">
    <i class="fas fa-shopping-bag fa-3x mb-3 d-block opacity-25"></i>
    <p>Nenhum pedido ainda.</p>
</div>
<?php else: ?>

<!-- Contadores -->
<?php
$counts = array_count_values(array_column($pedidos, 'status'));
?>
<div class="row g-2 mb-3">
    <?php foreach ($status_labels as $st => $info): ?>
    <div class="col-6 col-md-3">
        <div class="card text-center py-2">
            <div class="fw-bold text-<?= $info['cor'] ?>" style="font-size:1.5rem"><?= $counts[$st] ?? 0 ?></div>
            <div class="text-muted" style="font-size:.82rem"><?= $info['txt'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php foreach ($pedidos as $p):
    $info = $status_labels[$p['status']] ?? ['cor'=>'secondary','txt'=>$p['status']];
    $data = date('d/m/Y H:i', strtotime($p['criado_em']));
?>
<div class="card-pedido <?= $p['status'] ?>">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <strong>#<?= $p['id'] ?></strong>
            <span class="badge bg-<?= $info['cor'] ?> ms-2"><?= $info['txt'] ?></span>
            <div class="text-muted" style="font-size:.85rem"><?= htmlspecialchars($p['usuario_nome']) ?> · <?= htmlspecialchars($p['usuario_email']) ?></div>
            <div style="font-size:.82rem;color:#888"><?= $data ?></div>
            <?php if ($p['observacao']): ?>
            <div style="font-size:.82rem;color:#555;margin-top:3px">💬 <?= htmlspecialchars($p['observacao']) ?></div>
            <?php endif; ?>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <strong class="text-success">R$ <?= number_format($p['total'],2,',','.') ?></strong>
            <a href="?ver=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-eye"></i> Detalhes
            </a>
            <?php if ($p['status'] !== 'cancelado'): ?>
            <form method="POST" class="d-inline">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <select name="status" class="form-select form-select-sm d-inline-block" style="width:auto" onchange="this.form.submit()">
                    <?php foreach ($status_labels as $st => $si): ?>
                    <option value="<?= $st ?>" <?= $p['status'] === $st ? 'selected' : '' ?>><?= $si['txt'] ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

<?php if ($detalhes && $detalhes['pedido']): ?>
<!-- Modal automático ao carregar ?ver= -->
<div class="modal fade" id="modalDetalhes" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pedido #<?= $detalhes['pedido']['id'] ?></h5>
                <a href="pedidos_loja.php" class="btn-close"></a>
            </div>
            <div class="modal-body">
                <p><strong>Cliente:</strong> <?= htmlspecialchars($detalhes['pedido']['usuario_nome']) ?><br>
                <strong>Email:</strong> <?= htmlspecialchars($detalhes['pedido']['usuario_email']) ?><br>
                <strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($detalhes['pedido']['criado_em'])) ?></p>
                <table class="table table-sm">
                    <thead class="table-dark"><tr><th>Produto</th><th>Qtd</th><th>Preço unit.</th><th>Subtotal</th></tr></thead>
                    <tbody>
                    <?php foreach ($detalhes['itens'] as $it): ?>
                    <tr>
                        <td><?= htmlspecialchars($it['nome_produto']) ?></td>
                        <td><?= $it['quantidade'] ?></td>
                        <td>R$ <?= number_format($it['preco_unitario'],2,',','.') ?></td>
                        <td>R$ <?= number_format($it['quantidade']*$it['preco_unitario'],2,',','.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr><td colspan="3" class="fw-bold text-end">Total:</td><td class="fw-bold text-success">R$ <?= number_format($detalhes['pedido']['total'],2,',','.') ?></td></tr>
                    </tfoot>
                </table>
                <?php if ($detalhes['pedido']['observacao']): ?>
                <p class="text-muted"><strong>Obs:</strong> <?= htmlspecialchars($detalhes['pedido']['observacao']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>document.addEventListener('DOMContentLoaded',()=>new bootstrap.Modal(document.getElementById('modalDetalhes')).show());</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
