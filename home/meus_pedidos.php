<?php
header('Content-Type: text/html; charset=utf-8');
session_start();

if (!isset($_SESSION['LOGADO']) || $_SESSION['LOGADO'] !== true) {
    header('Location: ../login.php');
    exit;
}

include '../config/db.php';
include '../include/header.php';

$email = $conn->real_escape_string($_SESSION['usuario_logado'] ?? '');
$pedidos = [];
$res = $conn->query("SELECT * FROM pedidos WHERE usuario_email='$email' ORDER BY criado_em DESC");
if ($res) while ($r = $res->fetch_assoc()) $pedidos[] = $r;

$status_info = [
    'aguardando' => ['cor'=>'warning', 'txt'=>'Aguardando'],
    'confirmado' => ['cor'=>'primary', 'txt'=>'Confirmado'],
    'pronto'     => ['cor'=>'success', 'txt'=>'Pronto p/ Retirada'],
    'cancelado'  => ['cor'=>'danger',  'txt'=>'Cancelado'],
];

// Buscar itens se ver detalhes
$detalhe_pedido = null;
if (isset($_GET['ver'])) {
    $vid = (int)$_GET['ver'];
    $dp  = $conn->query("SELECT * FROM pedidos WHERE id=$vid AND usuario_email='$email'")->fetch_assoc();
    if ($dp) {
        $detalhe_pedido = $dp;
        $detalhe_pedido['itens'] = [];
        $ri = $conn->query("SELECT * FROM pedido_itens WHERE pedido_id=$vid");
        if ($ri) while ($r = $ri->fetch_assoc()) $detalhe_pedido['itens'][] = $r;
    }
}
?>

<style>
.pedidos-container { max-width:900px; margin:30px auto; padding:0 16px 60px; }
.pedidos-container h2 { color:#14532d; font-size:1.8rem; font-weight:700; margin-bottom:24px; }
.pedido-card {
    background:#fff; border-radius:14px; padding:16px 20px;
    box-shadow:0 2px 10px rgba(0,0,0,0.06); margin-bottom:12px;
    border-left:4px solid #e2e8f0; transition:box-shadow .2s;
}
.pedido-card:hover { box-shadow:0 4px 16px rgba(0,0,0,0.1); }
.pedido-card.aguardando { border-left-color:#f59e0b; }
.pedido-card.confirmado { border-left-color:#3b82f6; }
.pedido-card.pronto     { border-left-color:#22c55e; }
.pedido-card.cancelado  { border-left-color:#ef4444; opacity:.7; }
</style>

<div class="pedidos-container">
    <h2>📋 Meus Pedidos</h2>

    <?php if (empty($pedidos)): ?>
    <div style="text-align:center;padding:60px 20px;background:#fff;border-radius:16px;box-shadow:0 2px 8px rgba(0,0,0,0.06)">
        <div style="font-size:3.5rem;margin-bottom:12px">🛍️</div>
        <h4 style="color:#4b7c5c">Você ainda não fez nenhum pedido</h4>
        <a href="loja.php" class="btn btn-success mt-2" style="border-radius:10px;padding:10px 28px">Ver produtos</a>
    </div>
    <?php else: ?>
    <?php foreach ($pedidos as $p):
        $si   = $status_info[$p['status']] ?? ['cor'=>'secondary','txt'=>$p['status']];
        $data = date('d/m/Y H:i', strtotime($p['criado_em']));
    ?>
    <div class="pedido-card <?= $p['status'] ?>">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <strong>#<?= $p['id'] ?></strong>
                <span class="badge bg-<?= $si['cor'] ?> ms-2"><?= $si['txt'] ?></span>
                <div style="color:#888;font-size:.83rem;margin-top:2px"><?= $data ?></div>
                <?php if ($p['observacao']): ?>
                <div style="font-size:.82rem;color:#555;margin-top:2px">💬 <?= htmlspecialchars($p['observacao']) ?></div>
                <?php endif; ?>
            </div>
            <div class="d-flex align-items-center gap-3">
                <strong style="color:#15803d;font-size:1.1rem">R$ <?= number_format($p['total'],2,',','.') ?></strong>
                <a href="?ver=<?= $p['id'] ?>" class="btn btn-sm btn-outline-secondary" style="border-radius:8px">
                    <i class="fas fa-eye"></i> Detalhes
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php if ($detalhe_pedido): ?>
<div class="modal fade" id="modalDetalhe" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pedido #<?= $detalhe_pedido['id'] ?></h5>
                <a href="meus_pedidos.php" class="btn-close"></a>
            </div>
            <div class="modal-body">
                <?php $si = $status_info[$detalhe_pedido['status']] ?? ['cor'=>'secondary','txt'=>$detalhe_pedido['status']]; ?>
                <p>Status: <span class="badge bg-<?= $si['cor'] ?>"><?= $si['txt'] ?></span></p>
                <table class="table table-sm">
                    <thead class="table-dark"><tr><th>Produto</th><th>Qtd</th><th>Subtotal</th></tr></thead>
                    <tbody>
                    <?php foreach ($detalhe_pedido['itens'] as $it): ?>
                    <tr>
                        <td><?= htmlspecialchars($it['nome_produto']) ?></td>
                        <td><?= $it['quantidade'] ?></td>
                        <td>R$ <?= number_format($it['quantidade']*$it['preco_unitario'],2,',','.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr><td colspan="2" class="text-end fw-bold">Total:</td>
                        <td class="fw-bold text-success">R$ <?= number_format($detalhe_pedido['total'],2,',','.') ?></td></tr>
                    </tfoot>
                </table>
                <p class="text-muted" style="font-size:.85rem">🏪 Retirada na barbearia</p>
            </div>
        </div>
    </div>
</div>
<script>document.addEventListener('DOMContentLoaded',()=>new bootstrap.Modal(document.getElementById('modalDetalhe')).show());</script>
<?php endif; ?>

<?php include '../include/footer.php'; ?>
