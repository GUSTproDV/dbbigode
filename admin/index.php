<?php
header('Content-Type: text/html; charset=utf-8');
// Incluir conexão do banco primeiro
include_once('../config/db.php');

// Depois verificar se é admin
require_once('../include/admin_middleware.php');
verificarAdmin(); // Só admins podem acessar esta página

// Buscar estatísticas
$result_usuarios = $conn->query("SELECT COUNT(*) as total FROM usuario WHERE ativo = 1");
$total_usuarios = $result_usuarios->fetch_assoc()['total'];

$result_agendamentos = $conn->query("SELECT COUNT(*) as total FROM horarios");
$total_agendamentos = $result_agendamentos->fetch_assoc()['total'];

$result_hoje = $conn->query("SELECT COUNT(*) as total FROM horarios WHERE DATE(data) = CURDATE()");
$agendamentos_hoje = $result_hoje->fetch_assoc()['total'];

// Buscar próximos agendamentos
$result_proximos = $conn->query("
    SELECT h.*, u.nome as cliente_nome, u.email as cliente_email 
    FROM horarios h 
    LEFT JOIN usuario u ON h.nome = u.nome 
    WHERE h.data >= CURDATE() 
    ORDER BY h.data ASC, h.hora ASC 
    LIMIT 10
");
$proximos_agendamentos = [];
if ($result_proximos) {
    while ($row = $result_proximos->fetch_assoc()) {
        $proximos_agendamentos[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - DB Bigode</title>
    <link href="../assets/bootstrap.min.css" rel="stylesheet">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2c3e50;
        }
        .admin-menu {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .admin-btn {
            display: block;
            width: 100%;
            margin-bottom: 0.5rem;
            padding: 1rem;
            border-radius: 8px;
            text-decoration: none;
            text-align: left;
            transition: all 0.3s ease;
        }
        .admin-btn:hover {
            transform: translateY(-2px);
            text-decoration: none;
        }
        .table-container {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body style="background-color: #f8f9fa;">

<div class="admin-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1><i class="fas fa-crown"></i> Painel Administrativo</h1>
                <p>Bem-vindo ao sistema de administração da DB Bigode</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="../index.php" class="btn btn-light">
                    <i class="fas fa-home"></i> Voltar ao Site
                </a>
                <a href="../logout.php" class="btn btn-outline-light ms-2">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row">
        <!-- Estatísticas -->
        <div class="col-md-4">
            <div class="stat-card text-center">
                <div class="stat-number text-primary"><?php echo $total_usuarios; ?></div>
                <div>Usuários Cadastrados</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card text-center">
                <div class="stat-number text-success"><?php echo $total_agendamentos; ?></div>
                <div>Total de Agendamentos</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card text-center">
                <div class="stat-number text-warning"><?php echo $agendamentos_hoje; ?></div>
                <div>Agendamentos Hoje</div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Menu de Administração -->
        <div class="col-md-4">
            <div class="admin-menu">
                <h5 class="mb-3">Menu Administrativo</h5>
                
                <a href="usuarios.php" class="admin-btn btn btn-primary">
                    <i class="fas fa-users"></i> Gerenciar Usuários
                </a>
                
                <a href="agendamentos.php" class="admin-btn btn btn-success">
                    <i class="fas fa-calendar"></i> Gerenciar Agendamentos
                </a>
                
                <a href="horarios.php" class="admin-btn btn btn-info">
                    <i class="fas fa-clock"></i> Configurar Horários
                </a>
                
                <a href="servicos.php" class="admin-btn btn btn-warning">
                    <i class="fas fa-cut"></i> Gerenciar Serviços e Preços
                </a>
                
                <a href="relatorios.php" class="admin-btn btn btn-secondary">
                    <i class="fas fa-chart-bar"></i> Relatórios
                </a>
            </div>
        </div>

        <!-- Próximos Agendamentos -->
        <div class="col-md-8">
            <div class="table-container">
                <h5 class="mb-3">Próximos Agendamentos</h5>
                
                <?php if (empty($proximos_agendamentos)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Nenhum agendamento encontrado.
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
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($proximos_agendamentos as $agendamento): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($agendamento['nome']); ?></strong>
                                        <?php if ($agendamento['cliente_email']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($agendamento['cliente_email']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($agendamento['corte'] ?? 'Não especificado'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($agendamento['data'])); ?></td>
                                    <td><?php echo date('H:i', strtotime($agendamento['hora'])); ?></td>
                                    <td>
                                        <a href="agendamento_detalhes.php?id=<?php echo $agendamento['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            Ver Detalhes
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-end">
                        <a href="agendamentos.php" class="btn btn-primary">
                            Ver Todos os Agendamentos
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://kit.fontawesome.com/a076d05399.js"></script>
</body>
</html>