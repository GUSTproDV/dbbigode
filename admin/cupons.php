<?php
header('Content-Type: text/html; charset=utf-8');
include_once('../config/db.php');
require_once('../include/admin_middleware.php');
verificarSuperAdmin();

$sucesso = $erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'adicionar') {
        $codigo   = strtoupper(trim($_POST['codigo'] ?? ''));
        $descricao= trim($_POST['descricao'] ?? '');
        $tipo     = $_POST['tipo'] === 'fixo' ? 'fixo' : 'percentual';
        $valor    = (float)($_POST['valor'] ?? 0);
        $usos_max = $_POST['usos_max'] !== '' ? (int)$_POST['usos_max'] : null;
        $validade = $_POST['validade'] !== '' ? $_POST['validade'] : null;

        if (!$codigo || $valor <= 0) {
            $erro = 'Preencha o código e o valor do desconto.';
        } elseif ($tipo === 'percentual' && $valor > 100) {
            $erro = 'Desconto percentual não pode ser maior que 100%.';
        } else {
            $stmt = $conn->prepare("INSERT INTO cupons (codigo, descricao, tipo, valor, usos_max, validade) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("sssdi s", $codigo, $descricao, $tipo, $valor, $usos_max, $validade);
            $stmt->close();
            // Usa abordagem segura com tipos corretos
            $cod_s = $conn->real_escape_string($codigo);
            $des_s = $conn->real_escape_string($descricao);
            $tip_s = $conn->real_escape_string($tipo);
            $umax  = $usos_max !== null ? (int)$usos_max : 'NULL';
            $val_s = $validade ? "'{$conn->real_escape_string($validade)}'" : 'NULL';
            $ok = $conn->query("INSERT INTO cupons (codigo, descricao, tipo, valor, usos_max, validade) VALUES ('$cod_s','$des_s','$tip_s',$valor,$umax,$val_s)");
            $sucesso = $ok ? 'Cupom criado com sucesso!' : 'Código já existe.';
        }
    }

    if ($acao === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) { $conn->query("UPDATE cupons SET ativo = NOT ativo WHERE id=$id"); $sucesso = 'Status alterado!'; }
    }

    if ($acao === 'deletar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $usos = (int)$conn->query("SELECT usos_atual FROM cupons WHERE id=$id")->fetch_assoc()['usos_atual'];
            if ($usos > 0) { $erro = 'Não é possível excluir um cupom que já foi utilizado. Desative-o.'; }
            else { $conn->query("DELETE FROM cupons WHERE id=$id"); $sucesso = 'Cupom excluído.'; }
        }
    }
}

$cupons = [];
$res = $conn->query("SELECT * FROM cupons ORDER BY ativo DESC, criado_em DESC");
if ($res) while ($r = $res->fetch_assoc()) $cupons[] = $r;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cupons de Desconto - Admin</title>
<link href="../assets/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
body { background:#f8f9fa; }
.admin-header { background:linear-gradient(135deg,#0a0a0a 0%,#052e16 55%,#15803d 100%); color:#fff; padding:1.5rem 0; margin-bottom:2rem; box-shadow:0 4px 16px rgba(10,10,10,0.4); }
.cupom-card { background:#fff; border-radius:12px; padding:16px 20px; margin-bottom:10px; box-shadow:0 2px 8px rgba(0,0,0,0.07); display:flex; align-items:center; gap:16px; flex-wrap:wrap; border-left:4px solid #22c55e; }
.cupom-card.inativo { border-left-color:#94a3b8; opacity:.7; }
.cupom-codigo { font-family:monospace; font-size:1.15rem; font-weight:800; color:#14532d; background:#f0fdf4; padding:6px 14px; border-radius:8px; border:1px dashed #22c55e; letter-spacing:2px; }
.cupom-tipo-badge { font-size:.8rem; padding:3px 10px; border-radius:20px; font-weight:600; }
.tipo-perc { background:#dbeafe; color:#1d4ed8; }
.tipo-fixo { background:#fef3c7; color:#92400e; }
.cupom-meta { font-size:.83rem; color:#64748b; }
.usos-bar { height:5px; border-radius:3px; background:#e2e8f0; width:120px; margin-top:4px; }
.usos-fill { height:5px; border-radius:3px; background:#22c55e; }
</style>
</head>
<body>
<div class="admin-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-8">
                <h1><i class="fas fa-tag"></i> Cupons de Desconto</h1>
                <p>Crie e gerencie cupons para a loja</p>
            </div>
            <div class="col-4 text-end d-flex gap-2 justify-content-end">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAdd">
                    <i class="fas fa-plus"></i> Novo Cupom
                </button>
                <a href="index.php" class="btn btn-light"><i class="fas fa-arrow-left"></i> Voltar</a>
            </div>
        </div>
    </div>
</div>

<div class="container">
<?php if ($sucesso): ?><div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($sucesso) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($erro):    ?><div class="alert alert-danger  alert-dismissible fade show"><?= htmlspecialchars($erro) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<?php if (empty($cupons)): ?>
<div class="text-center py-5 text-muted">
    <i class="fas fa-tag fa-3x mb-3 d-block opacity-25"></i>
    Nenhum cupom cadastrado ainda.
</div>
<?php else: ?>
<?php foreach ($cupons as $c):
    $ativo     = (bool)$c['ativo'];
    $expirado  = $c['validade'] && $c['validade'] < date('Y-m-d');
    $lotado    = $c['usos_max'] && $c['usos_atual'] >= $c['usos_max'];
    $pct_uso   = $c['usos_max'] ? min(100, round($c['usos_atual']/$c['usos_max']*100)) : 0;
?>
<div class="cupom-card <?= $ativo ? '' : 'inativo' ?>">
    <div>
        <div class="cupom-codigo"><?= htmlspecialchars($c['codigo']) ?></div>
        <?php if (!$ativo): ?><div style="font-size:.75rem;color:#94a3b8;margin-top:3px">Desativado</div><?php endif; ?>
        <?php if ($expirado): ?><div style="font-size:.75rem;color:#ef4444;margin-top:3px">Expirado</div><?php endif; ?>
    </div>
    <div class="flex-grow-1">
        <?php if ($c['descricao']): ?><div style="font-weight:600;font-size:.93rem;color:#1a2e1a"><?= htmlspecialchars($c['descricao']) ?></div><?php endif; ?>
        <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
            <span class="cupom-tipo-badge <?= $c['tipo']==='percentual'?'tipo-perc':'tipo-fixo' ?>">
                <?= $c['tipo'] === 'percentual' ? $c['valor'].'% off' : 'R$ '.number_format($c['valor'],2,',','.') ?>
            </span>
            <?php if ($c['validade']): ?>
            <span class="cupom-meta"><i class="fas fa-calendar-alt"></i> Válido até <?= date('d/m/Y', strtotime($c['validade'])) ?></span>
            <?php endif; ?>
        </div>
        <div class="cupom-meta mt-1">
            Usado: <?= $c['usos_atual'] ?><?= $c['usos_max'] ? '/'.$c['usos_max'] : '' ?> vez<?= $c['usos_atual']!=1?'es':'' ?>
            <?php if ($c['usos_max']): ?>
            <div class="usos-bar"><div class="usos-fill" style="width:<?= $pct_uso ?>%"></div></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <form method="POST" class="d-inline">
            <input type="hidden" name="acao" value="toggle">
            <input type="hidden" name="id" value="<?= $c['id'] ?>">
            <button type="submit" class="btn btn-sm <?= $ativo ? 'btn-outline-warning' : 'btn-outline-success' ?>">
                <?= $ativo ? 'Desativar' : 'Ativar' ?>
            </button>
        </form>
        <?php if ($c['usos_atual'] == 0): ?>
        <form method="POST" class="d-inline" onsubmit="return confirm('Excluir cupom?')">
            <input type="hidden" name="acao" value="deletar">
            <input type="hidden" name="id" value="<?= $c['id'] ?>">
            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<!-- Modal Novo Cupom -->
<div class="modal fade" id="modalAdd" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="acao" value="adicionar">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="fas fa-tag"></i> Novo Cupom</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Código *</label>
                            <input type="text" name="codigo" class="form-control text-uppercase fw-bold"
                                placeholder="Ex: BIGODE10" required maxlength="50"
                                oninput="this.value=this.value.toUpperCase().replace(/\s/g,'')">
                            <small class="text-muted">Sem espaços, em maiúsculas</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Tipo de desconto *</label>
                            <select name="tipo" class="form-select" id="sel-tipo" onchange="atualizarLabel()">
                                <option value="percentual">Percentual (%)</option>
                                <option value="fixo">Valor fixo (R$)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" id="lbl-valor">Desconto (%) *</label>
                            <input type="number" name="valor" class="form-control" step="0.01" min="0.01" required placeholder="10">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Máx. de usos</label>
                            <input type="number" name="usos_max" class="form-control" min="1" placeholder="Ilimitado">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Descrição</label>
                            <input type="text" name="descricao" class="form-control" placeholder="Ex: 10% off no mês de aniversário">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Validade</label>
                            <input type="date" name="validade" class="form-control" min="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Criar Cupom</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function atualizarLabel() {
    const tipo = document.getElementById('sel-tipo').value;
    document.getElementById('lbl-valor').textContent = tipo === 'fixo' ? 'Desconto (R$) *' : 'Desconto (%) *';
}
</script>
</body>
</html>
