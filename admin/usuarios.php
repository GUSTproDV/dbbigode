<?php
header('Content-Type: text/html; charset=utf-8');
include_once('../config/db.php');
require_once('../include/admin_middleware.php');
verificarAdmin();

$is_super_admin = isSuperAdmin();

// ID do usuário logado (protege contra auto-bloqueio)
$stmt_eu = $conn->prepare("SELECT id FROM usuario WHERE email = ? AND ativo = 1");
$stmt_eu->bind_param("s", $_SESSION['usuario_logado']);
$stmt_eu->execute();
$meu_id = (string)($stmt_eu->get_result()->fetch_assoc()['id'] ?? '');
$stmt_eu->close();

// Processar ações
if ($_POST) {
    $acao       = $_POST['acao']       ?? '';
    $usuario_id = (string)($_POST['usuario_id'] ?? '');

    // Adicionar novo funcionário (apenas super admin)
    if ($acao === 'adicionar_funcionario' && $is_super_admin) {
        $novo_nome  = trim($_POST['novo_nome']  ?? '');
        $novo_email = trim($_POST['novo_email'] ?? '');
        $nova_senha = trim($_POST['nova_senha'] ?? '');

        if (!$novo_nome || !$novo_email || !$nova_senha) {
            $erro = "Preencha todos os campos.";
        } else {
            $stmt_chk = $conn->prepare("SELECT id FROM usuario WHERE email = ?");
            $stmt_chk->bind_param("s", $novo_email);
            $stmt_chk->execute();
            $existe = $stmt_chk->get_result()->fetch_assoc();
            $stmt_chk->close();

            if ($existe) {
                $erro = "Este e-mail já está cadastrado.";
            } else {
                $novo_id    = bin2hex(random_bytes(16));
                $senha_hash = md5($nova_senha);
                $stmt_ins   = $conn->prepare("INSERT INTO usuario (id, nome, email, senha, tipo_usuario, ativo) VALUES (?, ?, ?, ?, 'funcionario', 1)");
                $stmt_ins->bind_param("ssss", $novo_id, $novo_nome, $novo_email, $senha_hash);
                $stmt_ins->execute();
                $stmt_ins->close();
                $sucesso = "Funcionário adicionado com sucesso!";
            }
        }

    // Bloqueia qualquer ação sobre si mesmo
    } elseif ($usuario_id && $usuario_id === $meu_id) {
        $erro = "Você não pode alterar o seu próprio usuário.";

    } elseif ($acao === 'toggle_ativo' && $usuario_id) {
        $stmt_check = $conn->prepare("SELECT tipo_usuario FROM usuario WHERE id = ?");
        $stmt_check->bind_param("s", $usuario_id);
        $stmt_check->execute();
        $alvo = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();

        // Funcionário só pode ativar/desativar clientes; apenas admin superior age sobre funcionários/admins
        if (!$is_super_admin && $alvo && $alvo['tipo_usuario'] !== 'cliente') {
            $erro = "Apenas o administrador pode alterar o status de funcionários ou administradores.";
        } else {
            $stmt = $conn->prepare("UPDATE usuario SET ativo = NOT ativo WHERE id = ?");
            $stmt->bind_param("s", $usuario_id);
            $stmt->execute();
            $stmt->close();
            $sucesso = "Status do usuário alterado com sucesso!";
        }

    } elseif ($acao === 'dar_acesso' && $usuario_id && $is_super_admin) {
        $stmt = $conn->prepare("UPDATE usuario SET tipo_usuario = 'funcionario' WHERE id = ?");
        $stmt->bind_param("s", $usuario_id);
        $stmt->execute();
        $stmt->close();
        $sucesso = "Acesso ao painel concedido!";

    } elseif ($acao === 'remover_acesso' && $usuario_id && $is_super_admin) {
        $stmt_check = $conn->prepare("SELECT tipo_usuario FROM usuario WHERE id = ?");
        $stmt_check->bind_param("s", $usuario_id);
        $stmt_check->execute();
        $alvo = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();

        if ($alvo && $alvo['tipo_usuario'] === 'admin') {
            $erro = "Não é possível rebaixar um administrador superior.";
        } else {
            $stmt = $conn->prepare("UPDATE usuario SET tipo_usuario = 'cliente' WHERE id = ?");
            $stmt->bind_param("s", $usuario_id);
            $stmt->execute();
            $stmt->close();
            $sucesso = "Acesso ao painel removido!";
        }
    }
}

// Buscar todos os usuários
$result = $conn->query("
    SELECT id, nome, email, ativo,
           CASE WHEN tipo_usuario IN ('admin','funcionario','cliente') THEN tipo_usuario ELSE 'cliente' END as tipo_usuario,
           (SELECT COUNT(*) FROM horarios WHERE nome = usuario.nome) as total_agendamentos
    FROM usuario
    ORDER BY
        CASE WHEN tipo_usuario = 'admin' THEN 0
             WHEN tipo_usuario = 'funcionario' THEN 1
             ELSE 2
        END,
        nome ASC
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
            background: linear-gradient(135deg, #0a0a0a 0%, #052e16 55%, #15803d 100%);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 16px rgba(10, 10, 10, 0.4);
        }
        .user-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .badge-admin       { background-color: #b45309; }
        .badge-funcionario { background-color: #15803d; }
        .badge-cliente     { background-color: #1d4ed8; }
        .user-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }
    </style>
</head>
<body style="background-color: #f8f9fa;">

<div class="admin-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-7">
                <h1><i class="fas fa-users"></i> Gerenciar Usuários</h1>
                <p>Administre usuários, permissões e status</p>
            </div>
            <div class="col-md-5 text-md-end d-flex gap-2 justify-content-md-end mt-2 mt-md-0 flex-wrap">
                <?php if ($is_super_admin): ?>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addFuncionarioModal">
                    <i class="fas fa-user-plus"></i> Adicionar Funcionário
                </button>
                <?php endif; ?>
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
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($sucesso); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($erro)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($erro); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="user-card">
                <h5 class="mb-3">Lista de Usuários</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
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
                                <td><strong><?php echo htmlspecialchars($usuario['nome']); ?></strong></td>
                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td>
                                    <?php
                                    $tipo = $usuario['tipo_usuario'];
                                    if ($tipo === 'admin') {
                                        $badge_class = 'badge-admin';
                                        $label = '&#128081; Admin Superior';
                                    } elseif ($tipo === 'funcionario') {
                                        $badge_class = 'badge-funcionario';
                                        $label = '&#128296; Funcionário';
                                    } else {
                                        $badge_class = 'badge-cliente';
                                        $label = 'Cliente';
                                    }
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo $label; ?>
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

                                        <?php if ((string)$usuario['id'] === $meu_id): ?>
                                            <span class="badge bg-secondary">Você</span>

                                        <?php else: ?>

                                            <!-- Toggle Ativo/Inativo: admin pode em todos; funcionário só em clientes -->
                                            <?php if ($is_super_admin || $usuario['tipo_usuario'] === 'cliente'): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="acao" value="toggle_ativo">
                                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                <button type="submit"
                                                    class="btn btn-sm <?php echo $usuario['ativo'] ? 'btn-warning' : 'btn-success'; ?>"
                                                    onclick="return confirm('Confirma a alteração do status?')">
                                                    <?php echo $usuario['ativo'] ? 'Desativar' : 'Ativar'; ?>
                                                </button>
                                            </form>
                                            <?php endif; ?>

                                            <!-- Dar/Remover acesso ao painel: APENAS super admin -->
                                            <?php if ($is_super_admin && (string)$usuario['id'] !== $meu_id): ?>
                                                <?php if ($usuario['tipo_usuario'] === 'cliente'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="acao" value="dar_acesso">
                                                    <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success"
                                                        onclick="return confirm('Dar acesso ao painel para este funcionário?')">
                                                        Dar Acesso ao Painel
                                                    </button>
                                                </form>
                                                <?php elseif ($usuario['tipo_usuario'] === 'funcionario'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="acao" value="remover_acesso">
                                                    <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-secondary"
                                                        onclick="return confirm('Remover acesso ao painel deste funcionário?')">
                                                        Remover Acesso
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            <?php endif; ?>

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

<!-- Modal Adicionar Funcionário -->
<div class="modal fade" id="addFuncionarioModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> Adicionar Funcionário</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="acao" value="adicionar_funcionario">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nome completo</label>
                        <input type="text" name="novo_nome" class="form-control" placeholder="Nome do funcionário" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">E-mail</label>
                        <input type="email" name="novo_email" class="form-control" placeholder="email@exemplo.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Senha</label>
                        <input type="password" name="nova_senha" class="form-control" placeholder="Senha de acesso" required>
                    </div>
                    <p class="text-muted small mb-0">
                        O funcionário receberá acesso ao painel administrativo para gerenciar agendamentos.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Adicionar Funcionário
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://kit.fontawesome.com/a076d05399.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
