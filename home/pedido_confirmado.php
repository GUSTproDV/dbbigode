<?php
header('Content-Type: text/html; charset=utf-8');
session_start();

if (!isset($_SESSION['LOGADO']) || $_SESSION['LOGADO'] !== true) {
    header('Location: ../login.php');
    exit;
}

include '../config/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: loja.php'); exit; }

$email = $_SESSION['usuario_logado'] ?? '';
$pedido = $conn->query("SELECT * FROM pedidos WHERE id=$id AND usuario_email='" . $conn->real_escape_string($email) . "'")->fetch_assoc();
if (!$pedido) { header('Location: loja.php'); exit; }

$itens = [];
$res = $conn->query("SELECT * FROM pedido_itens WHERE pedido_id=$id");
if ($res) while ($r = $res->fetch_assoc()) $itens[] = $r;

include '../include/header.php';

$status_info = [
    'aguardando' => ['icon'=>'⏳','txt'=>'Aguardando confirmação','cor'=>'#f59e0b'],
    'confirmado' => ['icon'=>'✅','txt'=>'Confirmado','cor'=>'#3b82f6'],
    'pronto'     => ['icon'=>'🎉','txt'=>'Pronto para retirada!','cor'=>'#22c55e'],
    'cancelado'  => ['icon'=>'❌','txt'=>'Cancelado','cor'=>'#ef4444'],
];
$si = $status_info[$pedido['status']] ?? $status_info['aguardando'];
?>

<style>
.confirm-container { max-width:600px; margin:40px auto; padding:0 16px 60px; text-align:center; }
.confirm-card { background:#fff; border-radius:20px; padding:32px 28px; box-shadow:0 4px 24px rgba(0,0,0,0.08); border:1px solid #e8f5e9; }
.confirm-icon { font-size:4rem; margin-bottom:12px; }
.confirm-titulo { font-size:1.7rem; font-weight:800; color:#14532d; margin-bottom:6px; }
.confirm-sub { color:#666; margin-bottom:24px; }
.status-badge { display:inline-block; padding:6px 18px; border-radius:20px; font-weight:700; font-size:.9rem; color:#fff; margin-bottom:20px; }
.itens-lista { text-align:left; background:#f8fdf8; border-radius:12px; padding:16px; margin-bottom:20px; }
.item-linha { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid #e8f5e9; font-size:.93rem; }
.item-linha:last-child { border-bottom:none; }
.total-linha { display:flex; justify-content:space-between; padding-top:10px; margin-top:4px; border-top:2px solid #14532d; font-weight:800; font-size:1.1rem; }
.total-val { color:#15803d; }
.btn-loja { display:inline-block; background:linear-gradient(90deg,#15803d,#22c55e); color:#fff; padding:12px 28px; border-radius:12px; text-decoration:none; font-weight:700; margin-top:8px; transition:opacity .2s; }
.btn-loja:hover { opacity:.9; color:#fff; }
.btn-pedidos { display:inline-block; background:transparent; color:#15803d; border:1.5px solid #22c55e; padding:12px 28px; border-radius:12px; text-decoration:none; font-weight:600; margin-top:8px; margin-left:8px; }
</style>

<div class="confirm-container">
    <div class="confirm-card">
        <div class="confirm-icon">🎊</div>
        <div class="confirm-titulo">Pedido realizado!</div>
        <div class="confirm-sub">Seu pedido <strong>#<?= $pedido['id'] ?></strong> foi recebido com sucesso.</div>

        <div class="status-badge" style="background:<?= $si['cor'] ?>">
            <?= $si['icon'] ?> <?= $si['txt'] ?>
        </div>

        <div class="itens-lista">
            <?php foreach ($itens as $it): ?>
            <div class="item-linha">
                <span><?= htmlspecialchars($it['nome_produto']) ?> <span style="color:#888">×<?= $it['quantidade'] ?></span></span>
                <span>R$ <?= number_format($it['quantidade']*$it['preco_unitario'],2,',','.') ?></span>
            </div>
            <?php endforeach; ?>
            <div class="total-linha">
                <span>Total pago</span>
                <span class="total-val">R$ <?= number_format($pedido['total'],2,',','.') ?></span>
            </div>
        </div>

        <p style="color:#555;font-size:.9rem">
            🏪 Retire na barbearia quando o status mudar para <strong style="color:#22c55e">Pronto para retirada</strong>.
        </p>
        <?php if ($pedido['observacao']): ?>
        <p style="color:#888;font-size:.85rem">💬 Obs: <?= htmlspecialchars($pedido['observacao']) ?></p>
        <?php endif; ?>

        <a href="loja.php" class="btn-loja">🛍️ Continuar comprando</a>
        <a href="meus_pedidos.php" class="btn-pedidos">📋 Meus Pedidos</a>
    </div>
</div>

<?php include '../include/footer.php'; ?>
