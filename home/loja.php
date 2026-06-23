<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
include '../config/db.php';

// Adicionar ao carrinho via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_produto'])) {
    $pid = (int)$_POST['add_produto'];
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    if ($pid > 0) {
        if (!isset($_SESSION['carrinho'])) $_SESSION['carrinho'] = [];
        $_SESSION['carrinho'][$pid] = ($_SESSION['carrinho'][$pid] ?? 0) + $qty;
    }
    header('Location: loja.php?adicionado=1');
    exit;
}

include '../include/header.php';

// Buscar produtos
$produtos = [];
$busca    = trim($_GET['q'] ?? '');
$sql      = "SELECT * FROM produtos WHERE ativo = 1";
if ($busca) {
    $b   = $conn->real_escape_string($busca);
    $sql .= " AND (nome LIKE '%$b%' OR descricao LIKE '%$b%')";
}
$sql .= " ORDER BY nome ASC";
$res = $conn->query($sql);
if ($res) while ($r = $res->fetch_assoc()) $produtos[] = $r;

$total_carrinho = array_sum($_SESSION['carrinho'] ?? []);
?>

<style>
.loja-container { max-width:1200px; margin:0 auto; padding:30px 16px 60px; }
.loja-header { text-align:center; margin-bottom:32px; }
.loja-header h2 { color:#14532d; font-size:2rem; font-weight:700; margin-bottom:8px; }
.loja-header p  { color:#4b7c5c; }
.busca-bar { max-width:500px; margin:0 auto 28px; position:relative; }
.busca-bar input { border-radius:12px; padding:12px 48px 12px 18px; border:1.5px solid #bbf7d0; box-shadow:0 2px 8px rgba(0,0,0,0.05); }
.busca-bar input:focus { border-color:#22c55e; outline:none; box-shadow:0 0 0 3px rgba(34,197,94,0.15); }
.busca-bar button { position:absolute; right:8px; top:50%; transform:translateY(-50%); background:none; border:none; color:#15803d; font-size:1.1rem; cursor:pointer; }
.produtos-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:20px; }
.produto-card {
    background:#fff; border-radius:16px; overflow:hidden;
    box-shadow:0 3px 12px rgba(22,163,74,0.08); border:1px solid #e8f5e9;
    display:flex; flex-direction:column; transition:transform .2s, box-shadow .2s;
}
.produto-card:hover { transform:translateY(-4px); box-shadow:0 8px 24px rgba(22,163,74,0.15); }
.produto-card img { width:100%; height:180px; object-fit:cover; }
.produto-placeholder {
    width:100%; height:180px;
    background:linear-gradient(135deg,#14532d,#22c55e);
    display:flex; align-items:center; justify-content:center; font-size:3.5rem;
}
.produto-body { padding:14px 16px 16px; flex-grow:1; display:flex; flex-direction:column; }
.produto-nome { font-weight:700; color:#1a2e1a; font-size:.97rem; margin-bottom:4px; }
.produto-desc { color:#666; font-size:.82rem; margin-bottom:10px; flex-grow:1; }
.produto-preco { font-size:1.25rem; font-weight:700; color:#15803d; margin-bottom:12px; }
.produto-sem-estoque { font-size:.8rem; color:#ef4444; font-weight:600; margin-bottom:8px; }
.btn-add-cart {
    width:100%; padding:10px;
    background:linear-gradient(90deg,#15803d 55%,#22c55e 100%);
    color:#fff; border:none; border-radius:10px; font-weight:600;
    cursor:pointer; transition:background .2s, transform .15s;
}
.btn-add-cart:hover { background:linear-gradient(90deg,#14532d 55%,#15803d 100%); transform:scale(1.02); }
.btn-add-cart:disabled { background:#e2e8f0; color:#94a3b8; cursor:not-allowed; transform:none; }
.cart-float {
    position:fixed; bottom:28px; right:24px; z-index:100;
    background:linear-gradient(135deg,#14532d,#22c55e);
    color:#fff; border-radius:50px; padding:12px 22px;
    box-shadow:0 6px 24px rgba(21,128,61,0.4);
    text-decoration:none; font-weight:700; font-size:1rem;
    display:flex; align-items:center; gap:10px; transition:transform .2s;
}
.cart-float:hover { transform:scale(1.05); color:#fff; }
.cart-badge { background:#fff; color:#15803d; border-radius:50%; width:24px; height:24px; font-size:.8rem; display:flex; align-items:center; justify-content:center; font-weight:800; }
.alert-add { position:fixed; top:80px; right:20px; z-index:200; min-width:240px; animation:slideIn .3s ease; }
@keyframes slideIn { from{transform:translateX(100%);opacity:0} to{transform:translateX(0);opacity:1} }
.sem-produtos { text-align:center; padding:60px 20px; background:#fff; border-radius:16px; }
</style>

<div class="loja-container">
    <div class="loja-header">
        <h2>🧴 Nossa Loja</h2>
        <p>Produtos selecionados para cuidar do seu estilo</p>
    </div>

    <?php if (isset($_GET['adicionado'])): ?>
    <div class="alert alert-add alert-success alert-dismissible fade show">
        ✅ Produto adicionado ao carrinho!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Busca -->
    <form method="GET" class="busca-bar">
        <input type="text" name="q" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar produto...">
        <button type="submit"><i class="fas fa-search"></i></button>
    </form>

    <?php if (empty($produtos)): ?>
    <div class="sem-produtos">
        <div style="font-size:3rem;margin-bottom:12px">🛍️</div>
        <h4 style="color:#4b7c5c"><?= $busca ? 'Nenhum produto encontrado para "' . htmlspecialchars($busca) . '"' : 'Em breve novos produtos!' ?></h4>
        <?php if ($busca): ?><a href="loja.php" class="btn btn-outline-success mt-2">Ver todos os produtos</a><?php endif; ?>
    </div>
    <?php else: ?>
    <div class="produtos-grid">
        <?php foreach ($produtos as $p):
            $sem_est = $p['estoque'] <= 0;
            $img_src = $p['imagem'] ? '../uploads/produtos/' . htmlspecialchars($p['imagem']) : null;
        ?>
        <div class="produto-card">
            <?php if ($img_src): ?>
            <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($p['nome']) ?>">
            <?php else: ?>
            <div class="produto-placeholder">🧴</div>
            <?php endif; ?>
            <div class="produto-body">
                <div class="produto-nome"><?= htmlspecialchars($p['nome']) ?></div>
                <?php if ($p['descricao']): ?>
                <div class="produto-desc"><?= htmlspecialchars(mb_substr($p['descricao'],0,80)) ?><?= mb_strlen($p['descricao'])>80?'…':'' ?></div>
                <?php endif; ?>
                <div class="produto-preco">R$ <?= number_format($p['preco'],2,',','.') ?></div>
                <?php if ($sem_est): ?>
                <div class="produto-sem-estoque">❌ Sem estoque</div>
                <?php endif; ?>
                <form method="POST" class="d-flex gap-2 align-items-center">
                    <input type="hidden" name="add_produto" value="<?= $p['id'] ?>">
                    <input type="number" name="qty" value="1" min="1" max="<?= max(1,$p['estoque']) ?>"
                        class="form-control form-control-sm" style="width:58px" <?= $sem_est?'disabled':'' ?>>
                    <button type="submit" class="btn-add-cart" <?= $sem_est?'disabled':'' ?>>
                        🛒 Adicionar
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Botão flutuante do carrinho -->
<?php if ($total_carrinho > 0): ?>
<a href="carrinho.php" class="cart-float">
    🛒 Carrinho
    <span class="cart-badge"><?= $total_carrinho ?></span>
</a>
<?php endif; ?>

<script>
// Auto-fecha o alerta de adicionado
setTimeout(() => {
    const a = document.querySelector('.alert-add');
    if (a) a.style.display = 'none';
}, 3000);
</script>

<?php include '../include/footer.php'; ?>
