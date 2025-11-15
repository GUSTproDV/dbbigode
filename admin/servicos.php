<?php
header('Content-Type: text/html; charset=utf-8');
// Incluir conexão do banco primeiro
include_once('../config/db.php');

// Depois verificar se é admin
require_once('../include/admin_middleware.php');
verificarAdmin();

$msg = '';
$msg_class = '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'adicionar') {
        $nome = $_POST['nome'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $preco = $_POST['preco'] ?? 0;
        $duracao = $_POST['duracao'] ?? 30;
        
        if (!empty($nome) && $preco > 0) {
            $stmt = $conn->prepare("INSERT INTO servicos (nome, descricao, preco, duracao, ativo) VALUES (?, ?, ?, ?, 1)");
            $stmt->bind_param("ssdi", $nome, $descricao, $preco, $duracao);
            if ($stmt->execute()) {
                $msg = "Serviço adicionado com sucesso!";
                $msg_class = "alert-success";
            } else {
                $msg = "Erro ao adicionar serviço.";
                $msg_class = "alert-danger";
            }
        }
    }
    
    if ($acao === 'editar') {
        $id = $_POST['id'] ?? 0;
        $nome = $_POST['nome'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $preco = $_POST['preco'] ?? 0;
        $duracao = $_POST['duracao'] ?? 30;
        
        if ($id > 0 && !empty($nome) && $preco > 0) {
            $stmt = $conn->prepare("UPDATE servicos SET nome = ?, descricao = ?, preco = ?, duracao = ? WHERE id = ?");
            $stmt->bind_param("ssdii", $nome, $descricao, $preco, $duracao, $id);
            if ($stmt->execute()) {
                $msg = "Serviço atualizado com sucesso!";
                $msg_class = "alert-success";
            } else {
                $msg = "Erro ao atualizar serviço.";
                $msg_class = "alert-danger";
            }
        }
    }
    
    if ($acao === 'toggle_ativo') {
        $id = $_POST['id'] ?? 0;
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE servicos SET ativo = NOT ativo WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $msg = "Status alterado com sucesso!";
            $msg_class = "alert-success";
        }
    }
    
    if ($acao === 'deletar') {
        $id = $_POST['id'] ?? 0;
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM servicos WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $msg = "Serviço removido com sucesso!";
                $msg_class = "alert-success";
            }
        }
    }
}

// Buscar todos os serviços
$result = $conn->query("SELECT * FROM servicos ORDER BY ordem ASC, nome ASC");
$servicos = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $servicos[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Serviços e Preços - Admin</title>
    <link href="../assets/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
        }
        .servico-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .servico-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        .preco-destaque {
            font-size: 1.5rem;
            font-weight: bold;
            color: #28a745;
        }
        .badge-inativo {
            background-color: #6c757d;
        }
    </style>
</head>
<body style="background-color: #f8f9fa;">

<div class="admin-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1><i class="fas fa-cut"></i> Gerenciar Serviços e Preços</h1>
                <p>Configure os serviços oferecidos e seus valores</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="index.php" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Voltar ao Painel
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <?php if (!empty($msg)): ?>
        <div class="alert <?php echo $msg_class; ?> alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo $msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-12">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdicionar">
                <i class="fas fa-plus"></i> Adicionar Novo Serviço
            </button>
        </div>
    </div>

    <div class="row">
        <?php foreach ($servicos as $servico): ?>
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="servico-card">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="mb-0"><?php echo htmlspecialchars($servico['nome']); ?></h5>
                    <span class="badge <?php echo $servico['ativo'] ? 'bg-success' : 'badge-inativo'; ?>">
                        <?php echo $servico['ativo'] ? 'Ativo' : 'Inativo'; ?>
                    </span>
                </div>
                
                <p class="text-muted small mb-2">
                    <?php echo htmlspecialchars($servico['descricao']); ?>
                </p>
                
                <div class="preco-destaque mb-2">
                    R$ <?php echo number_format($servico['preco'], 2, ',', '.'); ?>
                </div>
                
                <div class="text-muted small mb-3">
                    <i class="fas fa-clock"></i> <?php echo $servico['duracao']; ?> minutos
                </div>
                
                <div class="btn-group w-100" role="group">
                    <button class="btn btn-sm btn-outline-primary" onclick="editarServico(<?php echo htmlspecialchars(json_encode($servico)); ?>)">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    <form method="POST" class="d-inline" style="flex: 1;">
                        <input type="hidden" name="acao" value="toggle_ativo">
                        <input type="hidden" name="id" value="<?php echo $servico['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-warning w-100">
                            <i class="fas fa-toggle-on"></i> <?php echo $servico['ativo'] ? 'Desativar' : 'Ativar'; ?>
                        </button>
                    </form>
                    <button class="btn btn-sm btn-outline-danger" onclick="confirmarDelete(<?php echo $servico['id']; ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal Adicionar -->
<div class="modal fade" id="modalAdicionar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Adicionar Novo Serviço</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="acao" value="adicionar">
                    
                    <div class="mb-3">
                        <label class="form-label">Nome do Serviço *</label>
                        <input type="text" name="nome" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea name="descricao" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Preço (R$) *</label>
                            <input type="number" name="preco" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Duração (min)</label>
                            <input type="number" name="duracao" class="form-control" value="30" min="5">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Adicionar Serviço</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="modalEditar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Serviço</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Nome do Serviço *</label>
                        <input type="text" name="nome" id="edit_nome" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <textarea name="descricao" id="edit_descricao" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Preço (R$) *</label>
                            <input type="number" name="preco" id="edit_preco" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Duração (min)</label>
                            <input type="number" name="duracao" id="edit_duracao" class="form-control" min="5">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Form oculto para deletar -->
<form method="POST" id="formDelete" style="display: none;">
    <input type="hidden" name="acao" value="deletar">
    <input type="hidden" name="id" id="delete_id">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editarServico(servico) {
    document.getElementById('edit_id').value = servico.id;
    document.getElementById('edit_nome').value = servico.nome;
    document.getElementById('edit_descricao').value = servico.descricao;
    document.getElementById('edit_preco').value = servico.preco;
    document.getElementById('edit_duracao').value = servico.duracao;
    
    var modal = new bootstrap.Modal(document.getElementById('modalEditar'));
    modal.show();
}

function confirmarDelete(id) {
    if (confirm('Tem certeza que deseja remover este serviço? Esta ação não pode ser desfeita.')) {
        document.getElementById('delete_id').value = id;
        document.getElementById('formDelete').submit();
    }
}
</script>

</body>
</html>
