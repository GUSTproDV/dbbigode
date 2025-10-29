<?php
// Incluir conexão do banco primeiro
include_once('../config/db.php');

// Depois verificar se é admin
require_once('../include/admin_middleware.php');
verificarAdmin();

// Processar exclusão de agendamento
if ($_POST && isset($_POST['excluir_agendamento'])) {
    $agendamento_id = $_POST['agendamento_id'];
    $stmt = $conn->prepare("DELETE FROM horarios WHERE id = ?");
    $stmt->bind_param("i", $agendamento_id);
    if ($stmt->execute()) {
        $sucesso = "Agendamento excluído com sucesso!";
    } else {
        $erro = "Erro ao excluir agendamento.";
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
        COUNT(CASE WHEN DATE(data) < CURDATE() THEN 1 END) as passados
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
        <div class="col-md-3">
            <div class="stats-card">
                <h3 class="text-primary"><?php echo $stats['total']; ?></h3>
                <div>Total</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <h3 class="text-warning"><?php echo $stats['hoje']; ?></h3>
                <div>Hoje</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <h3 class="text-success"><?php echo $stats['futuros']; ?></h3>
                <div>Futuros</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <h3 class="text-secondary"><?php echo $stats['passados']; ?></h3>
                <div>Passados</div>
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
                        
                        if ($data_agendamento < $hoje) {
                            $classe_row = 'passado';
                            $status = 'Realizado';
                            $badge_class = 'bg-secondary';
                        } elseif ($data_agendamento == $hoje) {
                            $classe_row = 'hoje';
                            $status = 'Hoje';
                            $badge_class = 'bg-warning';
                        } else {
                            $classe_row = 'futuro';
                            $status = 'Agendado';
                            $badge_class = 'bg-success';
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
                            <td><span class="badge <?php echo $badge_class; ?>"><?php echo $status; ?></span></td>
                            <td>
                                <span class="badge <?php echo $agendamento['tipo_usuario'] === 'admin' ? 'bg-danger' : 'bg-info'; ?>">
                                    <?php echo ucfirst($agendamento['tipo_usuario'] ?? 'cliente'); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-info" onclick="verDetalhes(<?php echo $agendamento['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
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