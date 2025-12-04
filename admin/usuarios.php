<?php
header('Content-Type: text/html; charset=utf-8');
// Incluir conexão do banco primeiro
include_once('../config/db.php');

// Depois verificar se é admin
require_once('../include/admin_middleware.php');
verificarAdmin();

// Processar ações (ativar/desativar usuário, promover a admin)
if ($_POST) {
    $acao = $_POST['acao'] ?? '';
    $usuario_id = $_POST['usuario_id'] ?? '';
    
    if ($acao === 'toggle_ativo' && $usuario_id) {
        $stmt = $conn->prepare("UPDATE usuario SET ativo = NOT ativo WHERE id = ?");
        $stmt->bind_param("s", $usuario_id);
        $stmt->execute();
        $sucesso = "Status do usuário alterado com sucesso!";
    }
    
    if ($acao === 'promover_admin' && $usuario_id) {
        $stmt = $conn->prepare("UPDATE usuario SET tipo_usuario = 'admin' WHERE id = ?");
        $stmt->bind_param("s", $usuario_id);
        $stmt->execute();
        $sucesso = "Usuário promovido a administrador!";
    }
    
    if ($acao === 'rebaixar_cliente' && $usuario_id) {
        $stmt = $conn->prepare("UPDATE usuario SET tipo_usuario = 'cliente' WHERE id = ?");
        $stmt->bind_param("s", $usuario_id);
        $stmt->execute();
        $sucesso = "Usuário rebaixado para cliente!";
    }
}

// Buscar todos os usuários
$result = $conn->query("
    SELECT id, nome, email, ativo, COALESCE(tipo_usuario, 'cliente') as tipo_usuario,
           (SELECT COUNT(*) FROM horarios WHERE nome = usuario.nome) as total_agendamentos
    FROM usuario 
    ORDER BY COALESCE(tipo_usuario, 'cliente') DESC, nome ASC
");
$usuarios = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Admin</title>
    <link href="../assets/bootstrap.min.css" rel="stylesheet">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #121416ff, #70490aff);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
        }
        .user-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .badge-admin {
            background-color: #dc3545;
        }
        .badge-cliente {
            background-color: #28a745;
        }
        .user-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
    </style>
</head>
<body style="background-color: #f8f9fa;">

<div class="admin-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1><i class="fas fa-users"></i> Gerenciar Usuários</h1>
                <p>Administre usuários, permissões e status</p>
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
    <?php if (isset($sucesso)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo $sucesso; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="user-card">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5>Lista de Usuários</h5>
                    
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Tipo</th>
                                <th>Status</th>
                                <th>Agendamentos</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td>
                                    <span class="badge <?php echo $usuario['tipo_usuario'] === 'admin' ? 'badge-admin' : 'badge-cliente'; ?>">
                                        <?php echo ucfirst($usuario['tipo_usuario']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $usuario['ativo'] ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $usuario['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo $usuario['total_agendamentos']; ?></span>
                                </td>
                                <td>
                                    <div class="user-actions">
                                        <!-- Toggle Ativo/Inativo -->
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="acao" value="toggle_ativo">
                                            <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                            <button type="submit" class="btn btn-sm <?php echo $usuario['ativo'] ? 'btn-warning' : 'btn-success'; ?>"
                                                    onclick="return confirm('Confirma a alteração do status?')">
                                                <?php echo $usuario['ativo'] ? 'Desativar' : 'Ativar'; ?>
                                            </button>
                                        </form>

                                        <!-- Promover/Rebaixar Admin -->
                                        <?php if ($usuario['tipo_usuario'] === 'cliente'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="acao" value="promover_admin">
                                            <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Promover este usuário a administrador?')">
                                                Promover Admin
                                            </button>
                                        </form>
                                        <?php elseif ($usuario['tipo_usuario'] === 'admin'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="acao" value="rebaixar_cliente">
                                            <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-secondary"
                                                    onclick="return confirm('Rebaixar este administrador para cliente?')">
                                                Rebaixar Cliente
                                            </button>
                                        </form>
                                        <?php endif; ?>

                                        
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Novo Usuário -->
<div class="modal fade" id="novoUsuarioModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Novo Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="../usuario/save.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" name="nome" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Senha</label>
                        <input type="password" name="senha" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de Usuário</label>
                        <select name="tipo_usuario" class="form-select">
                            <option value="cliente">Cliente</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Criar Usuário</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://kit.fontawesome.com/a076d05399.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function verDetalhes(userId) {
    // Implementar modal com detalhes do usuário
    alert('Função de detalhes será implementada. ID: ' + userId);
}
</script>
</body>
</html>