<?php
header('Content-Type: text/html; charset=utf-8');
include_once('../config/db.php');
require_once('../include/admin_middleware.php');
verificarSuperAdmin();

$sucesso = $erro = '';

// Configuração
$config = $conn->query("SELECT cortes_para_gratis FROM fidelidade_config WHERE id=1")->fetch_assoc();
$meta   = (int)($config['cortes_para_gratis'] ?? 10);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // Atualizar meta
    if ($acao === 'config') {
        $nova_meta = max(1, (int)($_POST['meta'] ?? 10));
        $conn->query("UPDATE fidelidade_config SET cortes_para_gratis=$nova_meta WHERE id=1");
        $meta    = $nova_meta;
        $sucesso = "Meta atualizada para $nova_meta cortes!";
    }

    // Dar carimbo
    if ($acao === 'stamp') {
        $email = trim($_POST['email'] ?? '');
        $nome  = trim($_POST['nome']  ?? '');
        $obs   = trim($_POST['obs']   ?? '');
        if ($email && $nome) {
            $e = $conn->real_escape_string($email);
            $n = $conn->real_escape_string($nome);
            $o = $conn->real_escape_string($obs);
            $conn->query("INSERT INTO fidelidade_pontos (usuario_email, usuario_nome, tipo, observacao) VALUES ('$e','$n','stamp','$o')");

            // Verificar se chegou à meta → anunciar
            $stamps   = (int)$conn->query("SELECT COUNT(*) as n FROM fidelidade_pontos WHERE usuario_email='$e' AND tipo='stamp'")->fetch_assoc()['n'];
            $resgates = (int)$conn->query("SELECT COUNT(*) as n FROM fidelidade_pontos WHERE usuario_email='$e' AND tipo='resgate'")->fetch_assoc()['n'];
            $disponiveis = floor($stamps / $meta) - $resgates;

            $sucesso = "Carimbo adicionado para $nome!";
            if ($disponiveis > 0) {
                $sucesso .= " 🎉 Corte grátis disponível!";
            }
        } else {
            $erro = 'Preencha o nome e o e-mail do cliente.';
        }
    }

    // Resgatar corte grátis
    if ($acao === 'resgate') {
        $email = trim($_POST['email'] ?? '');
        $nome  = trim($_POST['nome']  ?? '');
        if ($email) {
            $e = $conn->real_escape_string($email);
            $n = $conn->real_escape_string($nome);
            $stamps   = (int)$conn->query("SELECT COUNT(*) as n FROM fidelidade_pontos WHERE usuario_email='$e' AND tipo='stamp'")->fetch_assoc()['n'];
            $resgates = (int)$conn->query("SELECT COUNT(*) as n FROM fidelidade_pontos WHERE usuario_email='$e' AND tipo='resgate'")->fetch_assoc()['n'];
            $disponiveis = floor($stamps / $meta) - $resgates;

            if ($disponiveis <= 0) {
                $erro = 'Este cliente não tem cortes grátis disponíveis.';
            } else {
                $conn->query("INSERT INTO fidelidade_pontos (usuario_email, usuario_nome, tipo, observacao) VALUES ('$e','$n','resgate','Corte grátis resgatado')");
                $sucesso = "Corte grátis resgatado para $nome!";
            }
        }
    }
}

// Buscar clientes com pontos
$clientes = [];
$res = $conn->query("
    SELECT usuario_email, usuario_nome,
           SUM(tipo='stamp')   AS stamps,
           SUM(tipo='resgate') AS resgates,
           MAX(criado_em)      AS ultimo
    FROM fidelidade_pontos
    GROUP BY usuario_email
    ORDER BY stamps DESC
");
if ($res) while ($r = $res->fetch_assoc()) {
    $r['disponiveis'] = floor($r['stamps'] / $meta) - $r['resgates'];
    $r['progresso']   = $r['stamps'] % $meta;
    $clientes[] = $r;
}

// Histórico recente
$historico = [];
$res2 = $conn->query("SELECT * FROM fidelidade_pontos ORDER BY criado_em DESC LIMIT 30");
if ($res2) while ($r = $res2->fetch_assoc()) $historico[] = $r;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Fidelidade - Admin</title>
<link href="../assets/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
body { background:#f8f9fa; }
.admin-header { background:linear-gradient(135deg,#0a0a0a 0%,#052e16 55%,#15803d 100%); color:#fff; padding:1.5rem 0; margin-bottom:2rem; box-shadow:0 4px 16px rgba(10,10,10,0.4); }
.card-sec { background:#fff; border-radius:14px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,0.07); margin-bottom:20px; }
.stamp-grid { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; }
.stamp { width:28px; height:28px; border-radius:50%; border:2px solid #22c55e; display:flex; align-items:center; justify-content:center; font-size:.75rem; }
.stamp.filled { background:#22c55e; color:#fff; }
.stamp.empty  { background:#f0fdf4; color:#22c55e; }
.cliente-row { background:#fff; border-radius:10px; padding:14px 16px; margin-bottom:8px; box-shadow:0 1px 6px rgba(0,0,0,0.06); border-left:4px solid #e2e8f0; }
.cliente-row.tem-gratis { border-left-color:#22c55e; }
.badge-gratis { background:#dcfce7; color:#15803d; font-weight:700; border-radius:20px; padding:3px 12px; font-size:.82rem; }
</style>
</head>
<body>
<div class="admin-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-8">
                <h1><i class="fas fa-star"></i> Programa de Fidelidade</h1>
                <p>Cartão fidelidade digital · meta atual: <?= $meta ?> cortes = 1 grátis</p>
            </div>
            <div class="col-4 text-end">
                <a href="index.php" class="btn btn-light"><i class="fas fa-arrow-left"></i> Voltar</a>
            </div>
        </div>
    </div>
</div>

<div class="container">
<?php if ($sucesso): ?><div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($sucesso) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($erro):    ?><div class="alert alert-danger  alert-dismissible fade show"><?= htmlspecialchars($erro)   ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<div class="row g-3">
    <!-- Coluna esquerda: ações -->
    <div class="col-lg-4">

        <!-- Config da meta -->
        <div class="card-sec">
            <h6 class="fw-bold text-success mb-3"><i class="fas fa-cog"></i> Configuração</h6>
            <form method="POST">
                <input type="hidden" name="acao" value="config">
                <label class="form-label fw-semibold" style="font-size:.9rem">Cortes necessários para 1 grátis</label>
                <div class="input-group">
                    <input type="number" name="meta" class="form-control" value="<?= $meta ?>" min="1" max="99">
                    <button type="submit" class="btn btn-success">Salvar</button>
                </div>
            </form>
        </div>

        <!-- Dar carimbo -->
        <div class="card-sec">
            <h6 class="fw-bold text-success mb-3"><i class="fas fa-stamp"></i> Dar Carimbo</h6>
            <form method="POST">
                <input type="hidden" name="acao" value="stamp">
                <div class="mb-2">
                    <input type="email" name="email" class="form-control form-control-sm" placeholder="E-mail do cliente *" required list="lista-emails">
                    <datalist id="lista-emails">
                        <?php foreach ($clientes as $c): ?>
                        <option value="<?= htmlspecialchars($c['usuario_email']) ?>"><?= htmlspecialchars($c['usuario_nome']) ?></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="mb-2">
                    <input type="text" name="nome" class="form-control form-control-sm" placeholder="Nome do cliente *" required>
                </div>
                <div class="mb-2">
                    <input type="text" name="obs" class="form-control form-control-sm" placeholder="Observação (opcional)">
                </div>
                <button type="submit" class="btn btn-success w-100">
                    <i class="fas fa-stamp"></i> Carimbar Cartão
                </button>
            </form>
        </div>

        <!-- Resgatar corte grátis -->
        <div class="card-sec">
            <h6 class="fw-bold text-warning mb-3">🎉 Resgatar Corte Grátis</h6>
            <form method="POST">
                <input type="hidden" name="acao" value="resgate">
                <div class="mb-2">
                    <input type="email" name="email" class="form-control form-control-sm" placeholder="E-mail do cliente *" required list="lista-emails">
                </div>
                <div class="mb-2">
                    <input type="text" name="nome" class="form-control form-control-sm" placeholder="Nome do cliente" required>
                </div>
                <button type="submit" class="btn btn-warning w-100 fw-bold"
                    onclick="return confirm('Confirma o resgate do corte grátis?')">
                    ✂️ Usar Corte Grátis
                </button>
            </form>
        </div>
    </div>

    <!-- Coluna direita: clientes e histórico -->
    <div class="col-lg-8">
        <div class="card-sec">
            <h6 class="fw-bold mb-3"><i class="fas fa-users"></i> Clientes (<?= count($clientes) ?>)</h6>
            <?php if (empty($clientes)): ?>
            <p class="text-muted text-center py-3">Nenhum cliente no programa ainda.</p>
            <?php else: foreach ($clientes as $c):
                $progresso = (int)$c['progresso'];
            ?>
            <div class="cliente-row <?= $c['disponiveis'] > 0 ? 'tem-gratis' : '' ?>">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <strong><?= htmlspecialchars($c['usuario_nome']) ?></strong>
                        <div style="font-size:.82rem;color:#666"><?= htmlspecialchars($c['usuario_email']) ?></div>
                        <div style="font-size:.8rem;color:#94a3b8">Último: <?= date('d/m/Y', strtotime($c['ultimo'])) ?></div>
                    </div>
                    <div class="text-end">
                        <div style="font-size:.85rem;color:#555"><?= $c['stamps'] ?> carimbo<?= $c['stamps']!=1?'s':'' ?> · <?= $c['resgates'] ?> resgatado<?= $c['resgates']!=1?'s':'' ?></div>
                        <?php if ($c['disponiveis'] > 0): ?>
                        <span class="badge-gratis">✂️ <?= $c['disponiveis'] ?> grátis disponível</span>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Cartão visual -->
                <div class="stamp-grid mt-2">
                    <?php for ($i = 0; $i < $meta; $i++): ?>
                    <div class="stamp <?= $i < $progresso ? 'filled' : 'empty' ?>" title="<?= $i < $progresso ? 'Carimbo ' . ($i+1) : 'Vazio' ?>">
                        <?= $i < $progresso ? '✓' : '' ?>
                    </div>
                    <?php endfor; ?>
                </div>
                <div style="font-size:.8rem;color:#888;margin-top:4px"><?= $progresso ?>/<?= $meta ?> para o próximo grátis</div>
            </div>
            <?php endforeach; endif; ?>
        </div>

        <!-- Histórico -->
        <div class="card-sec">
            <h6 class="fw-bold mb-3"><i class="fas fa-history"></i> Histórico recente</h6>
            <?php if (empty($historico)): ?>
            <p class="text-muted">Nenhuma atividade ainda.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-dark">
                        <tr><th>Data</th><th>Cliente</th><th>Tipo</th><th>Obs</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($historico as $h): ?>
                    <tr>
                        <td style="font-size:.82rem"><?= date('d/m H:i', strtotime($h['criado_em'])) ?></td>
                        <td style="font-size:.85rem"><?= htmlspecialchars($h['usuario_nome']) ?></td>
                        <td>
                            <?php if ($h['tipo']==='stamp'): ?>
                            <span class="badge bg-success">Carimbo</span>
                            <?php else: ?>
                            <span class="badge bg-warning text-dark">Resgate</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:.82rem;color:#888"><?= htmlspecialchars($h['observacao'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
