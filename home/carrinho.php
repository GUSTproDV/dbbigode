<?php
header('Content-Type: text/html; charset=utf-8');
session_start();

if (!isset($_SESSION['LOGADO']) || $_SESSION['LOGADO'] !== true) {
    $_SESSION['redirect_after_login'] = 'carrinho.php';
    $_SESSION['login_message'] = "Faça login para ver seu carrinho.";
    header('Location: ../login.php');
    exit;
}

include '../config/db.php';

if (!isset($_SESSION['carrinho'])) $_SESSION['carrinho'] = [];

// ── AÇÕES ────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'remover') {
        $pid = (int)($_POST['produto_id'] ?? 0);
        unset($_SESSION['carrinho'][$pid]);
        header('Location: carrinho.php');
        exit;
    }

    if ($acao === 'atualizar') {
        foreach ($_POST['qty'] ?? [] as $pid => $qty) {
            $pid = (int)$pid;
            $qty = max(0, (int)$qty);
            if ($qty === 0) unset($_SESSION['carrinho'][$pid]);
            else            $_SESSION['carrinho'][$pid] = $qty;
        }
        header('Location: carrinho.php');
        exit;
    }

    if ($acao === 'limpar') {
        $_SESSION['carrinho'] = [];
        unset($_SESSION['cupom']);
        header('Location: loja.php');
        exit;
    }

    if ($acao === 'aplicar_cupom') {
        $codigo = strtoupper(trim($_POST['cupom'] ?? ''));
        $c_s    = $conn->real_escape_string($codigo);
        $cupom  = $conn->query("SELECT * FROM cupons WHERE codigo='$c_s' AND ativo=1")->fetch_assoc();
        if (!$cupom) {
            $_SESSION['cupom_erro'] = 'Cupom inválido ou desativado.';
        } elseif ($cupom['usos_max'] && $cupom['usos_atual'] >= $cupom['usos_max']) {
            $_SESSION['cupom_erro'] = 'Este cupom atingiu o limite de usos.';
        } elseif ($cupom['validade'] && $cupom['validade'] < date('Y-m-d')) {
            $_SESSION['cupom_erro'] = 'Este cupom está expirado.';
        } else {
            $_SESSION['cupom'] = $cupom;
            unset($_SESSION['cupom_erro']);
        }
        header('Location: carrinho.php');
        exit;
    }

    if ($acao === 'remover_cupom') {
        unset($_SESSION['cupom'], $_SESSION['cupom_erro']);
        header('Location: carrinho.php');
        exit;
    }
}

// ── BUSCAR PRODUTOS DO CARRINHO ──────────────────────────────────────────────
$itens = [];
$total = 0.0;

if (!empty($_SESSION['carrinho'])) {
    $ids = implode(',', array_map('intval', array_keys($_SESSION['carrinho'])));
    $res = $conn->query("SELECT id, nome, preco, estoque, imagem FROM produtos WHERE id IN ($ids) AND ativo = 1");
    while ($r = $res->fetch_assoc()) {
        $qty = (int)($_SESSION['carrinho'][$r['id']] ?? 0);
        // Limita quantidade ao estoque disponível
        $qty = min($qty, (int)$r['estoque']);
        if ($qty <= 0) { unset($_SESSION['carrinho'][$r['id']]); continue; }
        $_SESSION['carrinho'][$r['id']] = $qty;
        $r['qty'] = $qty;
        $r['subtotal'] = $r['preco'] * $qty;
        $total += $r['subtotal'];
        $itens[] = $r;
    }
}

include '../include/header.php';
?>

<style>
.carrinho-container { max-width:900px; margin:30px auto; padding:0 16px 60px; }
.carrinho-container h2 { color:#14532d; font-size:1.8rem; font-weight:700; margin-bottom:24px; }
.item-card {
    background:#fff; border-radius:14px; padding:14px 18px;
    box-shadow:0 2px 10px rgba(0,0,0,0.06); display:flex; align-items:center;
    gap:16px; margin-bottom:12px; border:1px solid #e8f5e9;
}
.item-img { width:72px; height:72px; border-radius:10px; object-fit:cover; flex-shrink:0; }
.item-placeholder { width:72px; height:72px; border-radius:10px; background:linear-gradient(135deg,#14532d,#22c55e); display:flex; align-items:center; justify-content:center; font-size:2rem; flex-shrink:0; }
.item-nome { font-weight:700; color:#1a2e1a; font-size:.97rem; }
.item-preco { color:#15803d; font-weight:700; font-size:1rem; }
.item-subtotal { color:#14532d; font-weight:700; font-size:1.05rem; white-space:nowrap; }
.qty-input { width:65px; text-align:center; border-radius:8px; border:1.5px solid #bbf7d0; padding:6px; font-size:.97rem; }
.resumo-card { background:#fff; border-radius:14px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,0.06); border:1px solid #e8f5e9; }
.resumo-total { font-size:1.5rem; font-weight:800; color:#15803d; }
.btn-finalizar {
    width:100%; padding:14px;
    background:linear-gradient(90deg,#15803d 55%,#22c55e 100%);
    color:#fff; border:none; border-radius:12px; font-size:1.05rem; font-weight:700;
    cursor:pointer; transition:background .2s; margin-top:8px;
}
.btn-finalizar:hover { background:linear-gradient(90deg,#14532d 55%,#15803d 100%); }
.btn-limpar { background:transparent; color:#ef4444; border:1px solid #ef4444; border-radius:10px; padding:8px 16px; cursor:pointer; font-size:.87rem; transition:background .2s; }
.btn-limpar:hover { background:#fee2e2; }
.carrinho-vazio { text-align:center; padding:60px 20px; background:#fff; border-radius:16px; box-shadow:0 2px 10px rgba(0,0,0,0.05); }
@media(max-width:600px) { .item-subtotal { display:none; } }
</style>

<div class="carrinho-container">
    <h2>🛒 Meu Carrinho</h2>

    <?php if (empty($itens)): ?>
    <div class="carrinho-vazio">
        <div style="font-size:4rem;margin-bottom:12px">🛒</div>
        <h4 style="color:#4b7c5c">Seu carrinho está vazio</h4>
        <p style="color:#888">Adicione produtos da nossa loja</p>
        <a href="loja.php" class="btn btn-success mt-2" style="border-radius:10px;padding:10px 28px">
            🛍️ Ver produtos
        </a>
    </div>

    <?php else: ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <form method="POST" id="form-atualizar">
                <input type="hidden" name="acao" value="atualizar">
                <?php foreach ($itens as $it):
                    $img = $it['imagem'] ? '../uploads/produtos/' . htmlspecialchars($it['imagem']) : null;
                ?>
                <div class="item-card">
                    <?php if ($img): ?>
                    <img src="<?= $img ?>" class="item-img" alt="<?= htmlspecialchars($it['nome']) ?>">
                    <?php else: ?>
                    <div class="item-placeholder">🧴</div>
                    <?php endif; ?>
                    <div class="flex-grow-1">
                        <div class="item-nome"><?= htmlspecialchars($it['nome']) ?></div>
                        <div class="item-preco">R$ <?= number_format($it['preco'],2,',','.') ?></div>
                    </div>
                    <input type="number" name="qty[<?= $it['id'] ?>]" value="<?= $it['qty'] ?>"
                        min="0" max="<?= $it['estoque'] ?>" class="qty-input"
                        onchange="document.getElementById('form-atualizar').submit()">
                    <div class="item-subtotal">R$ <?= number_format($it['subtotal'],2,',','.') ?></div>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="acao" value="remover">
                        <input type="hidden" name="produto_id" value="<?= $it['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" style="border-radius:8px" title="Remover">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </form>

            <div class="d-flex gap-2 mt-2">
                <a href="loja.php" class="btn btn-outline-success" style="border-radius:10px">← Continuar comprando</a>
                <form method="POST">
                    <input type="hidden" name="acao" value="limpar">
                    <button type="submit" class="btn-limpar" onclick="return confirm('Limpar carrinho?')">🗑️ Limpar carrinho</button>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="resumo-card">
                <h5 style="color:#14532d;font-weight:700;margin-bottom:16px">Resumo</h5>
                <?php foreach ($itens as $it): ?>
                <div class="d-flex justify-content-between" style="font-size:.9rem;margin-bottom:6px;color:#555">
                    <span><?= htmlspecialchars(mb_substr($it['nome'],0,28)) ?> ×<?= $it['qty'] ?></span>
                    <span>R$ <?= number_format($it['subtotal'],2,',','.') ?></span>
                </div>
                <?php endforeach; ?>
                <hr>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Total</span>
                    <span class="resumo-total">R$ <?= number_format($total,2,',','.') ?></span>
                </div>
                <p class="text-muted mt-2 mb-0" style="font-size:.82rem">🏪 Retirada na barbearia</p>

                <!-- Cupom -->
                <?php
                $cupom_aplicado = $_SESSION['cupom'] ?? null;
                $desconto_val   = 0.0;
                if ($cupom_aplicado) {
                    $desconto_val = $cupom_aplicado['tipo'] === 'percentual'
                        ? $total * ($cupom_aplicado['valor'] / 100)
                        : min((float)$cupom_aplicado['valor'], $total);
                }
                $total_final = max(0, $total - $desconto_val);
                ?>
                <?php if ($cupom_aplicado): ?>
                <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:10px 12px;margin-top:12px;font-size:.88rem">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>🏷️ Cupom <strong><?= htmlspecialchars($cupom_aplicado['codigo']) ?></strong></span>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="acao" value="remover_cupom">
                            <button type="submit" class="btn btn-sm btn-link text-danger p-0" style="font-size:.8rem">remover</button>
                        </form>
                    </div>
                    <div class="text-success fw-bold">− R$ <?= number_format($desconto_val,2,',','.') ?></div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <span class="fw-bold">Total com desconto</span>
                    <span class="resumo-total">R$ <?= number_format($total_final,2,',','.') ?></span>
                </div>
                <?php else: ?>
                <?php if (!empty($_SESSION['cupom_erro'])): ?>
                <div style="color:#ef4444;font-size:.82rem;margin-top:8px"><?= htmlspecialchars($_SESSION['cupom_erro']) ?></div>
                <?php unset($_SESSION['cupom_erro']); endif; ?>
                <form method="POST" class="d-flex gap-2 mt-3">
                    <input type="hidden" name="acao" value="aplicar_cupom">
                    <input type="text" name="cupom" class="form-control form-control-sm"
                        placeholder="Cupom de desconto" style="border-radius:8px;text-transform:uppercase">
                    <button type="submit" class="btn btn-sm btn-outline-success" style="border-radius:8px;white-space:nowrap">Aplicar</button>
                </form>
                <?php endif; ?>

                <a href="finalizar_pedido.php" class="btn-finalizar d-block text-center text-decoration-none mt-3">
                    Finalizar Pedido →
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../include/footer.php'; ?>
