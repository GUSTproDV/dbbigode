<?php
header('Content-Type: text/html; charset=utf-8');
// Incluir conexão do banco primeiro
include_once('../config/db.php');

// Depois verificar se é admin
require_once('../include/admin_middleware.php');
verificarAdmin();

$sucesso = '';
$erro = '';

// Processar ações
if ($_POST) {
    // Excluir agendamento
    if (isset($_POST['excluir_agendamento'])) {
        $agendamento_id = $_POST['agendamento_id'];
        $stmt = $conn->prepare("DELETE FROM horarios WHERE id = ?");
        $stmt->bind_param("i", $agendamento_id);
        if ($stmt->execute()) {
            $sucesso = "Agendamento excluído com sucesso!";
        } else {
            $erro = "Erro ao excluir agendamento.";
        }
    }
    
    // Marcar como realizado
    if (isset($_POST['marcar_realizado'])) {
        $agendamento_id = $_POST['agendamento_id'];
        $stmt = $conn->prepare("UPDATE horarios SET status = 'realizado' WHERE id = ?");
        $stmt->bind_param("i", $agendamento_id);
        if ($stmt->execute()) {
            $sucesso = "Agendamento marcado como realizado!";
        } else {
            $erro = "Erro ao atualizar status.";
        }
    }
    
    // Marcar como pendente
    if (isset($_POST['marcar_pendente'])) {
        $agendamento_id = $_POST['agendamento_id'];
        $stmt = $conn->prepare("UPDATE horarios SET status = 'pendente' WHERE id = ?");
        $stmt->bind_param("i", $agendamento_id);
        if ($stmt->execute()) {
            $sucesso = "Agendamento marcado como pendente!";
        } else {
            $erro = "Erro ao atualizar status.";
        }
    }
    
    // Marcar como cancelado
    if (isset($_POST['marcar_cancelado'])) {
        $agendamento_id = $_POST['agendamento_id'];
        $stmt = $conn->prepare("UPDATE horarios SET status = 'cancelado' WHERE id = ?");
        $stmt->bind_param("i", $agendamento_id);
        if ($stmt->execute()) {
            $sucesso = "Agendamento cancelado!";
        } else {
            $erro = "Erro ao atualizar status.";
        }
    }
}

// Filtros
$data_filtro = $_GET['data'] ?? '';
$nome_filtro = $_GET['nome'] ?? '';

// Construir query com filtros
$where_clause = '';
$query_params = [];
$param_types = '';

if ($data_filtro || $nome_filtro) {
    $conditions = [];
    if ($data_filtro) {
        $conditions[] = "DATE(h.data) = ?";
        $query_params[] = $data_filtro;
        $param_types .= 's';
    }
    if ($nome_filtro) {
        $conditions[] = "h.nome LIKE ?";
        $query_params[] = "%$nome_filtro%";
        $param_types .= 's';
    }
    $where_clause = 'WHERE ' . implode(' AND ', $conditions);
}

// Buscar agendamentos
$sql = "
    SELECT h.*, u.email as cliente_email, COALESCE(u.tipo_usuario, 'cliente') as tipo_usuario 
    FROM horarios h 
    LEFT JOIN usuario u ON h.nome = u.nome 
    $where_clause
    ORDER BY h.data ASC, h.hora ASC
";

if ($query_params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($param_types, ...$query_params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

$agendamentos = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $agendamentos[] = $row;
    }
}

// Estatísticas rápidas
$result_stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN DATE(data) = CURDATE() THEN 1 END) as hoje,
        COUNT(CASE WHEN DATE(data) > CURDATE() THEN 1 END) as futuros,
        COUNT(CASE WHEN DATE(data) < CURDATE() THEN 1 END) as passados,
        COUNT(CASE WHEN status = 'realizado' THEN 1 END) as realizados,
        COUNT(CASE WHEN status = 'pendente' THEN 1 END) as pendentes,
        COUNT(CASE WHEN status = 'cancelado' THEN 1 END) as cancelados
    FROM horarios
");
$stats = $result_stats->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Agendamentos - Admin</title>
    <link href="../assets/bootstrap.min.css" rel="stylesheet">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .agendamentos-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .agendamento-row.passado {
            opacity: 0.6;
            background-color: #f8f9fa;
        }
        .agendamento-row.hoje {
            background-color: #fff3cd;
        }
        .agendamento-row.futuro {
            background-color: #d1ecf1;
        }
        .dropdown-item form button {
            padding: 5px 10px;
        }
        .dropdown-menu {
            min-width: 220px;
        }
    </style>
</head>
<body style="background-color: #f8f9fa;">

<div class="admin-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1><i class="fas fa-calendar"></i> Gerenciar Agendamentos</h1>
                <p>Visualize e gerencie todos os agendamentos</p>
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

    <?php if (isset($erro)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?php echo $erro; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Estatísticas -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="stats-card">
                <h3 class="text-primary"><?php echo $stats['total']; ?></h3>
                <div>Total</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stats-card">
                <h3 class="text-warning"><?php echo $stats['hoje']; ?></h3>
                <div>Hoje</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stats-card">
                <h3 class="text-info"><?php echo $stats['futuros']; ?></h3>
                <div>Futuros</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stats-card">
                <h3 class="text-success"><?php echo $stats['realizados']; ?></h3>
                <div>Realizados</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stats-card">
                <h3 class="text-secondary"><?php echo $stats['pendentes']; ?></h3>
                <div>Pendentes</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stats-card">
                <h3 class="text-danger"><?php echo $stats['cancelados']; ?></h3>
                <div>Cancelados</div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="agendamentos-card mb-4">
        <h5>Filtros</h5>
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Data</label>
                <input type="date" name="data" class="form-control" value="<?php echo $data_filtro; ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Nome do Cliente</label>
                <input type="text" name="nome" class="form-control" value="<?php echo htmlspecialchars($nome_filtro); ?>" 
                       placeholder="Digite o nome...">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                <a href="agendamentos.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Limpar
                </a>
            </div>
        </form>
    </div>

    <!-- Lista de Agendamentos -->
    <div class="agendamentos-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5>Agendamentos (<?php echo count($agendamentos); ?>)</h5>
            <a href="../home/agendar.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Novo Agendamento
            </a>
        </div>

        <?php if (empty($agendamentos)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Nenhum agendamento encontrado com os filtros aplicados.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Cliente</th>
                            <th>Serviço</th>
                            <th>Data</th>
                            <th>Horário</th>
                            <th>Status</th>
                            <th>Tipo Cliente</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($agendamentos as $agendamento): ?>
                        <?php
                        $data_agendamento = $agendamento['data'];
                        $hoje = date('Y-m-d');
                        $status_agendamento = $agendamento['status'] ?? 'pendente';
                        
                        // Define classe da linha baseado na data
                        if ($data_agendamento < $hoje) {
                            $classe_row = 'passado';
                        } elseif ($data_agendamento == $hoje) {
                            $classe_row = 'hoje';
                        } else {
                            $classe_row = 'futuro';
                        }
                        
                        // Define badge do status
                        switch ($status_agendamento) {
                            case 'realizado':
                                $badge_status = '<span class="badge bg-success">Realizado</span>';
                                break;
                            case 'cancelado':
                                $badge_status = '<span class="badge bg-danger">Cancelado</span>';
                                break;
                            default:
                                $badge_status = '<span class="badge bg-warning text-dark">Pendente</span>';
                        }
                        ?>
                        <tr class="agendamento-row <?php echo $classe_row; ?>">
                            <td>
                                <strong><?php echo htmlspecialchars($agendamento['nome']); ?></strong>
                                <?php if ($agendamento['cliente_email']): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($agendamento['cliente_email']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($agendamento['corte'] ?? 'Não especificado'); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($agendamento['data'])); ?></td>
                            <td><?php echo date('H:i', strtotime($agendamento['hora'])); ?></td>
                            <td><?php echo $badge_status; ?></td>
                            <td>
                                <span class="badge <?php echo $agendamento['tipo_usuario'] === 'admin' ? 'bg-danger' : 'bg-info'; ?>">
                                    <?php echo ucfirst($agendamento['tipo_usuario'] ?? 'cliente'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <!-- Botão de Status -->
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-cog"></i> Status
                                        </button>
                                        <ul class="dropdown-menu">
                                            <?php if ($status_agendamento !== 'realizado'): ?>
                                            <li>
                                                <form method="POST" class="dropdown-item p-0">
                                                    <input type="hidden" name="marcar_realizado" value="1">
                                                    <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-link text-success text-decoration-none w-100 text-start">
                                                        <i class="fas fa-check"></i> Marcar como Realizado
                                                    </button>
                                                </form>
                                            </li>
                                            <?php endif; ?>
                                            <?php if ($status_agendamento !== 'pendente'): ?>
                                            <li>
                                                <form method="POST" class="dropdown-item p-0">
                                                    <input type="hidden" name="marcar_pendente" value="1">
                                                    <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-link text-warning text-decoration-none w-100 text-start">
                                                        <i class="fas fa-clock"></i> Marcar como Pendente
                                                    </button>
                                                </form>
                                            </li>
                                            <?php endif; ?>
                                            <?php if ($status_agendamento !== 'cancelado'): ?>
                                            <li>
                                                <form method="POST" class="dropdown-item p-0">
                                                    <input type="hidden" name="marcar_cancelado" value="1">
                                                    <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-link text-danger text-decoration-none w-100 text-start">
                                                        <i class="fas fa-times"></i> Marcar como Cancelado
                                                    </button>
                                                </form>
                                            </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                    
                                
                                    
                                    <!-- Botão Excluir -->
                                    <?php if ($data_agendamento >= $hoje): ?>
                                    <button class="btn btn-outline-danger" onclick="confirmarExclusao(<?php echo $agendamento['id']; ?>, '<?php echo htmlspecialchars($agendamento['nome']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para excluir agendamento -->
<form method="POST" id="formExcluir" style="display: none;">
    <input type="hidden" name="excluir_agendamento" value="1">
    <input type="hidden" name="agendamento_id" id="agendamentoIdExcluir">
</form>

<script src="https://kit.fontawesome.com/a076d05399.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmarExclusao(id, nome) {
    if (confirm(`Confirma a exclusão do agendamento de "${nome}"?`)) {
        document.getElementById('agendamentoIdExcluir').value = id;
        document.getElementById('formExcluir').submit();
    }
}

function verDetalhes(id) {
    alert('Detalhes do agendamento ID: ' + id + '\n\nEsta funcionalidade será implementada em breve.');
}
</script>
</body>
</html>