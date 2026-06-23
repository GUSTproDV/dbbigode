<?php
header('Content-Type: text/html; charset=utf-8');
session_start();

if (!isset($_SESSION['LOGADO']) || $_SESSION['LOGADO'] !== true) {
    header('Location: ../login.php');
    exit;
}

include '../config/db.php';

if (empty($_SESSION['carrinho'])) {
    header('Location: loja.php');
    exit;
}

// Buscar itens e validar estoque
$ids  = implode(',', array_map('intval', array_keys($_SESSION['carrinho'])));
$res  = $conn->query("SELECT id, nome, preco, estoque FROM produtos WHERE id IN ($ids) AND ativo = 1");
$itens = [];
$total = 0.0;
$erro  = '';

while ($r = $res->fetch_assoc()) {
    $qty = (int)($_SESSION['carrinho'][$r['id']] ?? 0);
    if ($qty > (int)$r['estoque']) {
        $erro = "O produto \"{$r['nome']}\" tem apenas {$r['estoque']} unidade(s) em estoque.";
        break;
    }
    $r['qty']      = $qty;
    $r['subtotal'] = $r['preco'] * $qty;
    $total        += $r['subtotal'];
    $itens[]       = $r;
}

// Processar pedido
if (!$erro && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $obs   = trim($_POST['observacao'] ?? '');
    $nome  = $_SESSION['NOME_USUARIO']    ?? '';
    $email = $_SESSION['usuario_logado']  ?? '';

    // Revalida estoque antes de salvar
    $conflito = '';
    foreach ($itens as $it) {
        $row = $conn->query("SELECT estoque FROM produtos WHERE id={$it['id']}")->fetch_assoc();
        if ($row['estoque'] < $it['qty']) {
            $conflito = "Estoque insuficiente para \"{$it['nome']}\". Atualize seu carrinho.";
            break;
        }
    }

    if ($conflito) {
        $erro = $conflito;
    } else {
        // Criar pedido
        $obs_s  = $conn->real_escape_string($obs);
        $nome_s = $conn->real_escape_string($nome);
        $eml_s  = $conn->real_escape_string($email);
        $conn->query("INSERT INTO pedidos (usuario_nome, usuario_email, total, observacao) VALUES ('$nome_s','$eml_s',$total,'$obs_s')");
        $pedido_id = $conn->insert_id;

        // Salvar itens e decrementar estoque
        foreach ($itens as $it) {
            $nome_p = $conn->real_escape_string($it['nome']);
            $conn->query("INSERT INTO pedido_itens (pedido_id, produto_id, nome_produto, quantidade, preco_unitario) VALUES ($pedido_id,{$it['id']},'$nome_p',{$it['qty']},{$it['preco']})");
            $conn->query("UPDATE produtos SET estoque = estoque - {$it['qty']} WHERE id = {$it['id']}");
        }

        $_SESSION['carrinho'] = [];
        header("Location: pedido_confirmado.php?id=$pedido_id");
        exit;
    }
}

include '../include/header.php';
?>

<style>
.finalizar-container { max-width:700px; margin:30px auto; padding:0 16px 60px; }
.finalizar-container h2 { color:#14532d; font-size:1.8rem; font-weight:700; margin-bottom:24px; }
.resumo-final { background:#fff; border-radius:14px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,0.06); border:1px solid #e8f5e9; margin-bottom:20px; }
.item-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #f0f4f0; font-size:.93rem; }
.item-row:last-child { border-bottom:none; }
.total-row { display:flex; justify-content:space-between; align-items:center; padding-top:12px; margin-top:4px; border-top:2px solid #14532d; }
.total-val { font-size:1.4rem; font-weight:800; color:#15803d; }
.btn-confirmar {
    width:100%; padding:15px; font-size:1.05rem; font-weight:700;
    background:linear-gradient(90deg,#15803d 55%,#22c55e 100%);
    color:#fff; border:none; border-radius:12px; cursor:pointer; transition:background .2s;
}
.btn-confirmar:hover { background:linear-gradient(90deg,#14532d 55%,#15803d 100%); }
</style>

<div class="finalizar-container">
    <h2>✅ Finalizar Pedido</h2>

    <?php if ($erro): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($erro) ?> <a href="carrinho.php">← Voltar ao carrinho</a></div>
    <?php endif; ?>

    <div class="resumo-final">
        <h5 style="color:#14532d;font-weight:700;margin-bottom:12px">Seus itens</h5>
        <?php foreach ($itens as $it): ?>
        <div class="item-row">
            <span><?= htmlspecialchars($it['nome']) ?> <span style="color:#888">×<?= $it['qty'] ?></span></span>
            <span style="font-weight:600;color:#15803d">R$ <?= number_format($it['subtotal'],2,',','.') ?></span>
        </div>
        <?php endforeach; ?>
        <div class="total-row">
            <span class="fw-bold">Total</span>
            <span class="total-val">R$ <?= number_format($total,2,',','.') ?></span>
        </div>
        <p class="text-muted mt-3 mb-0" style="font-size:.85rem">
            🏪 <strong>Retirada na barbearia</strong> · Você será avisado quando o pedido estiver pronto.
        </p>
    </div>

    <?php if (!$erro): ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label fw-semibold" style="color:#14532d">Observação (opcional)</label>
            <textarea name="observacao" class="form-control" rows="3"
                placeholder="Alguma observação sobre o seu pedido?" style="border-radius:10px;border-color:#bbf7d0"></textarea>
        </div>
        <button type="submit" class="btn-confirmar">Confirmar Pedido</button>
    </form>
    <?php endif; ?>

    <a href="carrinho.php" class="btn btn-outline-secondary mt-3 w-100" style="border-radius:10px">← Voltar ao carrinho</a>
</div>

<?php include '../include/footer.php'; ?>
