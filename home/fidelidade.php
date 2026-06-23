<?php
header('Content-Type: text/html; charset=utf-8');
session_start();

if (!isset($_SESSION['LOGADO']) || $_SESSION['LOGADO'] !== true) {
    $_SESSION['redirect_after_login'] = 'fidelidade.php';
    $_SESSION['login_message'] = "Faça login para ver seu cartão fidelidade.";
    header('Location: ../login.php');
    exit;
}

include '../config/db.php';
include '../include/header.php';

$email = $_SESSION['usuario_logado'] ?? '';
$nome  = $_SESSION['NOME_USUARIO']   ?? '';
$e     = $conn->real_escape_string($email);

$config   = $conn->query("SELECT cortes_para_gratis FROM fidelidade_config WHERE id=1")->fetch_assoc();
$meta     = (int)($config['cortes_para_gratis'] ?? 10);
$stamps   = (int)$conn->query("SELECT COUNT(*) as n FROM fidelidade_pontos WHERE usuario_email='$e' AND tipo='stamp'")->fetch_assoc()['n'];
$resgates = (int)$conn->query("SELECT COUNT(*) as n FROM fidelidade_pontos WHERE usuario_email='$e' AND tipo='resgate'")->fetch_assoc()['n'];
$disponiveis = floor($stamps / $meta) - $resgates;
$progresso   = $stamps % $meta;
$pct         = $meta > 0 ? round($progresso / $meta * 100) : 0;

$historico = [];
$res = $conn->query("SELECT * FROM fidelidade_pontos WHERE usuario_email='$e' ORDER BY criado_em DESC LIMIT 20");
if ($res) while ($r = $res->fetch_assoc()) $historico[] = $r;
?>

<style>
.fid-container { max-width:700px; margin:32px auto; padding:0 16px 60px; }
.fid-container h2 { color:#14532d; font-size:1.8rem; font-weight:800; text-align:center; margin-bottom:6px; }
.fid-container .subtitle { text-align:center; color:#4b7c5c; margin-bottom:28px; }
.cartao {
    background:linear-gradient(135deg,#0a2a14 0%,#14532d 60%,#15803d 100%);
    border-radius:20px; padding:28px 24px;
    box-shadow:0 12px 40px rgba(21,128,61,0.35);
    color:#fff; margin-bottom:24px; position:relative; overflow:hidden;
}
.cartao::before {
    content:''; position:absolute; top:-60px; right:-60px;
    width:200px; height:200px; border-radius:50%;
    background:rgba(255,255,255,0.04);
}
.cartao-titulo { font-size:.85rem; letter-spacing:2px; text-transform:uppercase; color:#86efac; margin-bottom:4px; }
.cartao-nome { font-size:1.25rem; font-weight:700; margin-bottom:20px; }
.stamp-grid { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:18px; }
.stamp {
    width:44px; height:44px; border-radius:50%;
    border:2px solid rgba(255,255,255,0.3);
    display:flex; align-items:center; justify-content:center;
    font-size:1.1rem; transition:all .2s;
    background:rgba(255,255,255,0.06);
}
.stamp.filled {
    background:#22c55e; border-color:#22c55e;
    box-shadow:0 0 12px rgba(34,197,94,0.5);
}
.stamp.gratis {
    background:#fbbf24; border-color:#fbbf24;
    box-shadow:0 0 16px rgba(251,191,36,0.6);
    animation:pulse 1.5s ease-in-out infinite;
}
@keyframes pulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.12)} }
.cartao-meta { font-size:.85rem; color:#86efac; margin-top:4px; }
.barra-container { background:rgba(255,255,255,0.12); border-radius:20px; height:10px; margin-bottom:8px; overflow:hidden; }
.barra-fill { height:100%; border-radius:20px; background:linear-gradient(90deg,#22c55e,#86efac); transition:width .6s ease; }
.gratis-banner {
    background:linear-gradient(90deg,#f59e0b,#fbbf24);
    border-radius:14px; padding:16px 20px; text-align:center;
    color:#fff; font-weight:700; font-size:1rem; margin-bottom:20px;
    box-shadow:0 4px 16px rgba(245,158,11,0.35);
    animation:pulse 2s ease-in-out infinite;
}
.card-sec { background:#fff; border-radius:14px; padding:18px; box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:16px; }
.hist-item { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #f0f4f0; font-size:.88rem; }
.hist-item:last-child { border-bottom:none; }
</style>

<div class="fid-container">
    <h2>⭐ Cartão Fidelidade</h2>
    <p class="subtitle">A cada <?= $meta ?> cortes, você ganha 1 corte grátis!</p>

    <?php if ($disponiveis > 0): ?>
    <div class="gratis-banner">
        🎉 Você tem <?= $disponiveis ?> corte<?= $disponiveis > 1 ? 's' : '' ?> grátis disponível<?= $disponiveis > 1 ? 'is' : '' ?>!<br>
        <span style="font-size:.9rem;font-weight:400">Informe ao barbeiro na sua próxima visita.</span>
    </div>
    <?php endif; ?>

    <!-- Cartão visual -->
    <div class="cartao">
        <div class="cartao-titulo">DB Bigode · Fidelidade</div>
        <div class="cartao-nome"><?= htmlspecialchars($nome) ?></div>

        <div class="stamp-grid">
            <?php for ($i = 0; $i < $meta; $i++):
                $filled = $i < $progresso;
                $is_last = $i === ($meta - 1);
            ?>
            <div class="stamp <?= $is_last ? 'gratis' : ($filled ? 'filled' : '') ?>"
                 title="<?= $filled ? 'Carimbo '.($i+1) : ($is_last ? 'Grátis!' : 'Vazio') ?>">
                <?= $filled ? '✓' : ($is_last ? '🎁' : '') ?>
            </div>
            <?php endfor; ?>
        </div>

        <div class="barra-container">
            <div class="barra-fill" style="width:<?= $pct ?>%"></div>
        </div>
        <div class="cartao-meta"><?= $progresso ?>/<?= $meta ?> cortes para o próximo grátis</div>

        <div style="position:absolute;bottom:18px;right:20px;font-size:.75rem;color:rgba(255,255,255,0.4)">
            ✂️ <?= $stamps ?> total · <?= $resgates ?> resgatado<?= $resgates != 1 ? 's' : '' ?>
        </div>
    </div>

    <!-- Como funciona -->
    <div class="card-sec">
        <h6 style="color:#14532d;font-weight:700;margin-bottom:12px">Como funciona?</h6>
        <div class="d-flex flex-column gap-2">
            <div style="font-size:.9rem;color:#555"><span style="color:#22c55e;font-weight:700">1.</span> Venha cortar o cabelo na DB Bigode</div>
            <div style="font-size:.9rem;color:#555"><span style="color:#22c55e;font-weight:700">2.</span> Peça para o barbeiro carimbar seu cartão</div>
            <div style="font-size:.9rem;color:#555"><span style="color:#22c55e;font-weight:700">3.</span> A cada <?= $meta ?> carimbos, ganhe 1 corte grátis 🎁</div>
        </div>
    </div>

    <!-- Histórico -->
    <?php if (!empty($historico)): ?>
    <div class="card-sec">
        <h6 style="color:#14532d;font-weight:700;margin-bottom:12px"><i class="fas fa-history"></i> Histórico</h6>
        <?php foreach ($historico as $h): ?>
        <div class="hist-item">
            <div>
                <?php if ($h['tipo'] === 'stamp'): ?>
                <span style="color:#22c55e">✓ Carimbo</span>
                <?php else: ?>
                <span style="color:#f59e0b">🎁 Corte grátis usado</span>
                <?php endif; ?>
                <?php if ($h['observacao']): ?>
                <span style="color:#94a3b8;font-size:.8rem"> · <?= htmlspecialchars($h['observacao']) ?></span>
                <?php endif; ?>
            </div>
            <span style="color:#94a3b8;font-size:.8rem"><?= date('d/m/Y', strtotime($h['criado_em'])) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include '../include/footer.php'; ?>
