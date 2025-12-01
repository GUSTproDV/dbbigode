<?php
header('Content-Type: text/html; charset=utf-8');
// Incluir conexão do banco primeiro
include_once('../config/db.php');

// Depois verificar se é admin
require_once('../include/admin_middleware.php');
verificarAdmin();

// Buscar dados para relatórios
$result_usuarios_mes = $conn->query("
    SELECT COUNT(*) as total 
    FROM usuario 
    WHERE MONTH(CURDATE()) = MONTH(CURDATE()) AND YEAR(CURDATE()) = YEAR(CURDATE())
");

$result_agendamentos_mes = $conn->query("
    SELECT COUNT(*) as total 
    FROM horarios 
    WHERE MONTH(data) = MONTH(CURDATE()) AND YEAR(data) = YEAR(CURDATE())
");

$result_agendamentos_semana = $conn->query("
    SELECT 
        DATE_FORMAT(data, '%Y-%m-%d') as dia,
        COUNT(*) as total 
    FROM horarios 
    WHERE data >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY data 
    ORDER BY data ASC
");

$agendamentos_semana = [];
if ($result_agendamentos_semana) {
    while ($row = $result_agendamentos_semana->fetch_assoc()) {
        $agendamentos_semana[] = $row;
    }
}

// Estatísticas por serviço
$result_servicos = $conn->query("
    SELECT 
        COALESCE(corte, 'Não especificado') as servico,
        COUNT(*) as total 
    FROM horarios 
    GROUP BY corte 
    ORDER BY total DESC
");
$servicos_stats = [];
if ($result_servicos) {
    while ($row = $result_servicos->fetch_assoc()) {
        $servicos_stats[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Admin</title>
    <link href="../assets/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .admin-header {
            background: linear-gradient(135deg, #121416ff, #70490aff);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
        }
        .chart-card, .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-item {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #6f42c1;
        }
    </style>
</head>
<body style="background-color: #f8f9fa;">

<div class="admin-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1><i class="fas fa-chart-bar"></i> Relatórios e Estatísticas</h1>
                <p>Análise de dados e geração de relatórios PDF</p>
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
    <!-- Geração de Relatórios PDF -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="stats-card">
                <h5 class="mb-4"><i class="fas fa-file-pdf"></i> Gerar Relatórios em PDF</h5>
                
                <form id="formRelatorio" method="GET" action="gerar_relatorio.php" target="_blank">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Data Início</label>
                            <input type="date" name="data_inicio" class="form-control" value="<?php echo date('Y-m-01'); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Data Fim</label>
                            <input type="date" name="data_fim" class="form-control" value="<?php echo date('Y-m-t'); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tipo de Relatório</label>
                            <select name="tipo" class="form-control" required>
                                <option value="">Selecione...</option>
                                <option value="cortes_frequentes">Cortes Mais Frequentes</option>
                                <option value="clientes_frequentes">Clientes Mais Frequentes</option>
                                <option value="disponibilidade">Disponibilidade de Agenda</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-danger btn-lg w-100">
                                <i class="fas fa-file-pdf"></i> Gerar Relatório PDF
                            </button>
                        </div>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="card border-primary">
                            <div class="card-body text-center">
                                <i class="fas fa-cut fa-3x text-primary mb-3"></i>
                                <h6>Cortes Mais Frequentes</h6>
                                <p class="small text-muted">Ranking dos serviços mais solicitados com estatísticas de realização</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-success">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-3x text-success mb-3"></i>
                                <h6>Clientes Mais Frequentes</h6>
                                <p class="small text-muted">Análise dos clientes mais assíduos e seus hábitos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-info">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar-alt fa-3x text-info mb-3"></i>
                                <h6>Disponibilidade de Agenda</h6>
                                <p class="small text-muted">Ocupação por dia da semana e horários mais procurados</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Resumo Geral -->
    <div class="row">
        <div class="col-md-6">
            <div class="stats-card">
                <h5>Usuários Este Mês</h5>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $result_usuarios_mes->fetch_assoc()['total']; ?></div>
                    <div>Novos Usuários</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="stats-card">
                <h5>Agendamentos Este Mês</h5>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $result_agendamentos_mes->fetch_assoc()['total']; ?></div>
                    <div>Total de Agendamentos</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico de Agendamentos por Dia -->
    <div class="row">
        <div class="col-12">
            <div class="chart-card">
                <h5>Agendamentos dos Últimos 7 Dias</h5>
                <canvas id="agendamentosChart" height="100"></canvas>
            </div>
        </div>
    </div>

    <!-- Estatísticas por Serviço -->
    <div class="row">
        <div class="col-md-6">
            <div class="stats-card">
                <h5>Serviços Mais Populares</h5>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Serviço</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($servicos_stats as $servico): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($servico['servico']); ?></td>
                                <td><span class="badge bg-primary"><?php echo $servico['total']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="chart-card">
                <h5>Distribuição de Serviços</h5>
                <canvas id="servicosChart"></canvas>
            </div>
        </div>
    </div>



<script src="https://kit.fontawesome.com/a076d05399.js"></script>
<script>
// Gráfico de agendamentos por dia
const ctx1 = document.getElementById('agendamentosChart').getContext('2d');
const agendamentosData = {
    labels: [<?php foreach($agendamentos_semana as $item) echo "'" . date('d/m', strtotime($item['dia'])) . "',"; ?>],
    datasets: [{
        label: 'Agendamentos',
        data: [<?php foreach($agendamentos_semana as $item) echo $item['total'] . ','; ?>],
        backgroundColor: 'rgba(111, 66, 193, 0.2)',
        borderColor: 'rgba(111, 66, 193, 1)',
        borderWidth: 2,
        fill: true
    }]
};

new Chart(ctx1, {
    type: 'line',
    data: agendamentosData,
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Gráfico de serviços
const ctx2 = document.getElementById('servicosChart').getContext('2d');
const servicosData = {
    labels: [<?php foreach($servicos_stats as $item) echo "'" . htmlspecialchars($item['servico']) . "',"; ?>],
    datasets: [{
        data: [<?php foreach($servicos_stats as $item) echo $item['total'] . ','; ?>],
        backgroundColor: [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'
        ]
    }]
};

new Chart(ctx2, {
    type: 'doughnut',
    data: servicosData,
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Funções administrativas
function exportarRelatorio() {
    alert('Função de exportação será implementada em breve.');
}

function limparAgendamentosAntigos() {
    if (confirm('Deseja realmente limpar agendamentos antigos (mais de 6 meses)?')) {
        alert('Função de limpeza será implementada em breve.');
    }
}

function enviarRelatorioEmail() {
    const email = prompt('Digite o email para envio do relatório:');
    if (email) {
        alert('Função de envio por email será implementada em breve.');
    }
}
</script>
</body>
</html>