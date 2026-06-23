<?php
header('Content-Type: text/html; charset=utf-8');
include_once('../config/db.php');
require_once('../include/admin_middleware.php');
verificarSuperAdmin();

$sucesso = $erro = '';

// ── UPLOAD DE IMAGEM ───────────────────────────────────────────────────────────
function salvarImagem($file, $conn) {
    $allowed  = ['image/jpeg','image/png','image/webp'];
    $max_size = 4 * 1024 * 1024;
    if ($file['error'] !== UPLOAD_ERR_OK)     return [null, 'Erro no upload.'];
    if (!in_array($file['type'], $allowed))    return [null, 'Formato inválido. Use JPG, PNG ou WEBP.'];
    if ($file['size'] > $max_size)             return [null, 'Arquivo muito grande. Máximo 4MB.'];
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = bin2hex(random_bytes(8)) . '.' . $ext;
    $dest     = dirname(__FILE__) . '/../uploads/produtos/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) return [null, 'Não foi possível salvar a imagem.'];
    return [$filename, null];
}

// ── AÇÕES POST ─────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // Adicionar produto
    if ($acao === 'adicionar') {
        $nome      = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $preco     = (float)($_POST['preco'] ?? 0);
        $estoque   = (int)($_POST['estoque'] ?? 0);
        $est_min   = (int)($_POST['estoque_minimo'] ?? 3);
        $imagem    = null;

        if (!$nome || $preco <= 0) {
            $erro = 'Preencha o nome e o preço.';
        } else {
            if (!empty($_FILES['imagem']['name'])) {
                [$imagem, $img_erro] = salvarImagem($_FILES['imagem'], $conn);
                if ($img_erro) { $erro = $img_erro; goto fim; }
            }
            $stmt = $conn->prepare("INSERT INTO produtos (nome, descricao, preco, estoque, estoque_minimo, imagem) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param("ssdiss", $nome, $descricao, $preco, $estoque, $est_min, $imagem);
            $stmt->execute();
            $stmt->close();
            $sucesso = 'Produto adicionado com sucesso!';
        }
    }

    // Editar produto
    if ($acao === 'editar') {
        $id        = (int)($_POST['id'] ?? 0);
        $nome      = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $preco     = (float)($_POST['preco'] ?? 0);
        $est_min   = (int)($_POST['estoque_minimo'] ?? 3);
        $ativo     = isset($_POST['ativo']) ? 1 : 0;

        if ($id && $nome && $preco > 0) {
            $imagem_sql = '';
            if (!empty($_FILES['imagem']['name'])) {
                [$nova_img, $img_erro] = salvarImagem($_FILES['imagem'], $conn);
                if ($img_erro) { $erro = $img_erro; goto fim; }
                // Apaga imagem antiga
                $old = $conn->query("SELECT imagem FROM produtos WHERE id=$id")->fetch_assoc();
                if ($old['imagem'] && file_exists(dirname(__FILE__) . '/../uploads/produtos/' . $old['imagem'])) {
                    unlink(dirname(__FILE__) . '/../uploads/produtos/' . $old['imagem']);
                }
                $img_esc    = $conn->real_escape_string($nova_img);
                $imagem_sql = ", imagem='$img_esc'";
            }
            $nome_s = $conn->real_escape_string($nome);
            $desc_s = $conn->real_escape_string($descricao);
            $conn->query("UPDATE produtos SET nome='$nome_s', descricao='$desc_s', preco=$preco, estoque_minimo=$est_min, ativo=$ativo $imagem_sql WHERE id=$id");
            $sucesso = 'Produto atualizado!';
        }
    }

    // Ajuste de estoque
    if ($acao === 'ajustar_estoque') {
        $id        = (int)($_POST['id'] ?? 0);
        $quantidade = (int)($_POST['quantidade'] ?? 0);
        $tipo      = $_POST['tipo'] ?? 'add'; // add | sub
        if ($id) {
            if ($tipo === 'sub') {
                $conn->query("UPDATE produtos SET estoque = GREATEST(0, estoque - $quantidade) WHERE id=$id");
            } else {
                $conn->query("UPDATE produtos SET estoque = estoque + $quantidade WHERE id=$id");
            }
            $sucesso = 'Estoque atualizado!';
        }
    }

    // Deletar produto
    if ($acao === 'deletar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            // Verifica se existe em algum pedido
            $n_pedidos = (int)$conn->query("SELECT COUNT(*) as n FROM pedido_itens WHERE produto_id=$id")->fetch_assoc()['n'];

            if ($n_pedidos > 0) {
                // Produto tem histórico de pedidos: apenas desativa (não deleta)
                $conn->query("UPDATE produtos SET ativo = 0 WHERE id = $id");
                $sucesso = "Produto desativado e removido da loja. Ele possui {$n_pedidos} item(s) em pedidos anteriores — o histórico dos clientes foi preservado.";
            } else {
                // Sem pedidos: pode deletar com segurança
                $old = $conn->query("SELECT imagem FROM produtos WHERE id=$id")->fetch_assoc();
                if ($old && $old['imagem'] && file_exists(dirname(__FILE__) . '/../uploads/produtos/' . $old['imagem'])) {
                    unlink(dirname(__FILE__) . '/../uploads/produtos/' . $old['imagem']);
                }
                $conn->query("DELETE FROM produtos WHERE id = $id");
                $sucesso = 'Produto removido com sucesso.';
            }
        }
    }
}
fim:

// Buscar produtos
$produtos = [];
$res = $conn->query("SELECT * FROM produtos ORDER BY ativo DESC, nome ASC");
if ($res) while ($r = $res->fetch_assoc()) $produtos[] = $r;

$estoque_baixo = array_filter($produtos, fn($p) => $p['ativo'] && $p['estoque'] <= $p['estoque_minimo']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Produtos - Admin</title>
<link href="../assets/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
body { background:#f8f9fa; }
.admin-header {
    background: linear-gradient(135deg, #0a0a0a 0%, #052e16 55%, #15803d 100%);
    color:#fff; padding:1.5rem 0; margin-bottom:2rem;
    box-shadow:0 4px 16px rgba(10,10,10,0.4);
}
.produto-card {
    background:#fff; border-radius:12px; overflow:hidden;
    box-shadow:0 2px 10px rgba(0,0,0,0.08); transition:transform .2s;
    border:1px solid #e2e8f0; height:100%;
}
.produto-card:hover { transform:translateY(-3px); box-shadow:0 6px 20px rgba(0,0,0,0.12); }
.produto-img { width:100%; height:160px; object-fit:cover; background:#f0f4f0; }
.produto-img-placeholder {
    width:100%; height:160px; background:linear-gradient(135deg,#14532d,#22c55e);
    display:flex; align-items:center; justify-content:center; font-size:3rem;
}
.produto-body { padding:14px; }
.produto-preco { font-size:1.3rem; font-weight:700; color:#15803d; }
.estoque-badge { font-size:.78rem; padding:3px 10px; border-radius:20px; font-weight:600; }
.estoque-ok    { background:#dcfce7; color:#15803d; }
.estoque-baixo { background:#fef3c7; color:#92400e; }
.estoque-zero  { background:#fee2e2; color:#991b1b; }
.badge-inativo { background:#e2e8f0; color:#64748b; }
.btn-acao { padding:5px 10px; font-size:.82rem; border-radius:8px; border:none; cursor:pointer; }
.alerta-estoque { background:#fef3c7; border:1px solid #f59e0b; border-radius:10px; padding:12px 16px; margin-bottom:1.5rem; }
</style>
</head>
<body>
<div class="admin-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-8">
                <h1><i class="fas fa-box-open"></i> Gerenciar Produtos</h1>
                <p>Catálogo, estoque e preços da loja</p>
            </div>
            <div class="col-4 text-end d-flex gap-2 justify-content-end">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAdicionar">
                    <i class="fas fa-plus"></i> Novo Produto
                </button>
                <a href="index.php" class="btn btn-light"><i class="fas fa-arrow-left"></i> Voltar</a>
            </div>
        </div>
    </div>
</div>

<div class="container">
<?php if ($sucesso): ?>
<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($sucesso) ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($erro): ?>
<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($erro) ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<?php if (!empty($estoque_baixo)): ?>
<div class="alerta-estoque">
    <strong>⚠️ Estoque baixo:</strong>
    <?php foreach ($estoque_baixo as $p): ?>
    <span class="badge bg-warning text-dark ms-1"><?= htmlspecialchars($p['nome']) ?> (<?= $p['estoque'] ?> un.)</span>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="row g-3">
<?php foreach ($produtos as $p):
    $est = (int)$p['estoque'];
    $min = (int)$p['estoque_minimo'];
    if (!$p['ativo'])       { $est_cls = 'badge-inativo'; $est_txt = 'Inativo'; }
    elseif ($est === 0)     { $est_cls = 'estoque-zero';  $est_txt = 'Sem estoque'; }
    elseif ($est <= $min)   { $est_cls = 'estoque-baixo'; $est_txt = "Baixo ($est un.)"; }
    else                    { $est_cls = 'estoque-ok';    $est_txt = "$est un."; }
    $img_src = $p['imagem'] ? '../uploads/produtos/' . htmlspecialchars($p['imagem']) : null;
?>
<div class="col-6 col-md-4 col-lg-3">
    <div class="produto-card d-flex flex-column">
        <?php if ($img_src): ?>
        <img src="<?= $img_src ?>" class="produto-img" alt="<?= htmlspecialchars($p['nome']) ?>">
        <?php else: ?>
        <div class="produto-img-placeholder">🧴</div>
        <?php endif; ?>
        <div class="produto-body flex-grow-1">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <strong style="font-size:.93rem"><?= htmlspecialchars($p['nome']) ?></strong>
                <span class="estoque-badge <?= $est_cls ?>"><?= $est_txt ?></span>
            </div>
            <?php if ($p['descricao']): ?>
            <p style="font-size:.8rem;color:#64748b;margin:4px 0 6px;"><?= htmlspecialchars(mb_substr($p['descricao'],0,70)) ?>…</p>
            <?php endif; ?>
            <div class="produto-preco mb-2">R$ <?= number_format($p['preco'],2,',','.') ?></div>

            <!-- Ajuste de estoque rápido -->
            <form method="POST" class="d-flex gap-1 mb-2 align-items-center">
                <input type="hidden" name="acao" value="ajustar_estoque">
                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                <input type="hidden" name="tipo" id="tipo_<?= $p['id'] ?>" value="add">
                <input type="number" name="quantidade" value="1" min="1" max="9999"
                    class="form-control form-control-sm" style="width:60px">
                <button type="submit" class="btn btn-sm btn-success btn-acao"
                    onclick="document.getElementById('tipo_<?= $p['id'] ?>').value='add'" title="Adicionar">
                    <i class="fas fa-plus"></i>
                </button>
                <button type="submit" class="btn btn-sm btn-outline-danger btn-acao"
                    onclick="document.getElementById('tipo_<?= $p['id'] ?>').value='sub'" title="Retirar">
                    <i class="fas fa-minus"></i>
                </button>
            </form>

            <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-primary flex-grow-1"
                    onclick="abrirEditar(<?= htmlspecialchars(json_encode($p)) ?>)">
                    <i class="fas fa-edit"></i>
                </button>
                <form method="POST" class="d-inline" onsubmit="return confirm('Remover produto?')">
                    <input type="hidden" name="acao" value="deletar">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php if (empty($produtos)): ?>
<div class="col-12 text-center py-5 text-muted">
    <i class="fas fa-box-open fa-3x mb-3 d-block opacity-25"></i>
    Nenhum produto cadastrado. Clique em <strong>Novo Produto</strong> para começar.
</div>
<?php endif; ?>
</div>
</div>

<!-- Modal Adicionar -->
<div class="modal fade" id="modalAdicionar" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="acao" value="adicionar">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Novo Produto</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Nome *</label>
                            <input type="text" name="nome" class="form-control" required placeholder="Ex: Pomada Modeladora">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Preço (R$) *</label>
                            <input type="number" name="preco" class="form-control" step="0.01" min="0.01" required placeholder="0,00">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Descrição</label>
                            <textarea name="descricao" class="form-control" rows="2" placeholder="Descrição do produto..."></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Estoque inicial</label>
                            <input type="number" name="estoque" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Estoque mínimo</label>
                            <input type="number" name="estoque_minimo" class="form-control" value="3" min="0">
                            <small class="text-muted">Alerta quando atingir este valor</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Foto do produto</label>
                            <input type="file" name="imagem" class="form-control" accept="image/jpeg,image/png,image/webp">
                            <small class="text-muted">JPG, PNG ou WEBP · max 4MB</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Adicionar Produto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" name="id" id="e_id">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Editar Produto</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold">Nome *</label>
                            <input type="text" name="nome" id="e_nome" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Preço (R$) *</label>
                            <input type="number" name="preco" id="e_preco" class="form-control" step="0.01" min="0.01" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Descrição</label>
                            <textarea name="descricao" id="e_descricao" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Estoque mínimo (alerta)</label>
                            <input type="number" name="estoque_minimo" id="e_est_min" class="form-control" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nova foto (opcional)</label>
                            <input type="file" name="imagem" class="form-control" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="ativo" id="e_ativo" value="1">
                                <label class="form-check-label" for="e_ativo">Produto ativo (visível na loja)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function abrirEditar(p) {
    document.getElementById('e_id').value      = p.id;
    document.getElementById('e_nome').value    = p.nome;
    document.getElementById('e_preco').value   = p.preco;
    document.getElementById('e_descricao').value = p.descricao || '';
    document.getElementById('e_est_min').value = p.estoque_minimo;
    document.getElementById('e_ativo').checked = p.ativo == 1;
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}
</script>
</body>
</html>
