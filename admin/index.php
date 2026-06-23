<?php
header('Content-Type: text/html; charset=utf-8');
// Incluir conexão do banco primeiro
include_once('../config/db.php');

// Depois verificar se é admin
require_once('../include/admin_middleware.php');
verificarAdmin();

$is_super_admin = isSuperAdmin();
$tipo_atual     = getTipoAtual();

// Buscar estatísticas
$result_usuarios = $conn->query("SELECT COUNT(*) as total FROM usuario WHERE ativo = 1");
$total_usuarios = $result_usuarios->fetch_assoc()['total'];

$result_agendamentos = $conn->query("SELECT COUNT(*) as total FROM horarios");
$total_agendamentos = $result_agendamentos->fetch_assoc()['total'];

$result_hoje = $conn->query("SELECT COUNT(*) as total FROM horarios WHERE DATE(data) = CURDATE()");
$agendamentos_hoje = $result_hoje->fetch_assoc()['total'];

// Buscar próximos agendamentos (apenas pendentes)
$result_proximos = $conn->query("
    SELECT h.*, u.nome as cliente_nome, u.email as cliente_email 
    FROM horarios h 
    LEFT JOIN usuario u ON h.nome = u.nome 
    WHERE h.data >= CURDATE() 
    AND h.status = 'pendente'
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
    <title><?php echo $is_super_admin ? 'Painel Administrativo' : 'Painel do Funcionário'; ?> - DB Bigode</title>
    <link href="../assets/bootstrap.min.css" rel="stylesheet">
    <style>
        .admin-header {
            background: linear-gradient(120deg, #0a0a0a 0%, #052e16 55%, #15803d 100%);
            color: #ffffff;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 16px rgba(10, 10, 10, 0.4);
        }
        .admin-header a.btn-light,
        .admin-header a.btn-outline-light {
            color: #14532d;
            border-color: rgba(34, 197, 94, 0.4);
            background: #f0fdf4;
        }
        .admin-header a.btn-light:hover,
        .admin-header a.btn-outline-light:hover {
            background: #dcfce7;
            border-color: #22c55e;
        }
        .cargo-badge {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
            margin-left: 8px;
            vertical-align: middle;
        }
        .cargo-super { background: rgba(255,215,0,0.18); color: #ffd700; border: 1px solid rgba(255,215,0,0.35); }
        .cargo-func  { background: rgba(34,197,94,0.15);  color: #4ade80; border: 1px solid rgba(34,197,94,0.3); }
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
            margin-bottom: 0.75rem;
            padding: 1rem;
            border-radius: 12px;
            text-decoration: none;
            text-align: left;
            transition: transform 0.25s ease, box-shadow 0.25s ease, background 0.25s ease;
            background: #fff;
            color: #14532d;
            border: 1px solid #bbf7d0;
            box-shadow: 0 2px 10px rgba(22, 163, 74, 0.06);
        }
        .admin-btn:hover {
            transform: translateY(-2px);
            background: linear-gradient(90deg, #14532d 0%, #15803d 100%);
            color: #fff;
            border-color: transparent;
            text-decoration: none;
            box-shadow: 0 4px 16px rgba(21, 128, 61, 0.25);
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
                <?php if ($is_super_admin): ?>
                    <h1><i class="fas fa-crown"></i> Painel Administrativo
                        <span class="cargo-badge cargo-super">Admin Superior</span>
                    </h1>
                <?php else: ?>
                    <h1><i class="fas fa-user-tie"></i> Painel do Funcionário
                        <span class="cargo-badge cargo-func">Funcionário</span>
                    </h1>
                <?php endif; ?>
                <p>Bem-vindo ao sistema de administração da DB Bigode</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="../home/index.php" class="btn btn-light">
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
    <?php if (isset($_GET['erro']) && $_GET['erro'] === 'acesso_restrito'): ?>
        <div class="alert alert-danger alert-dismissible fade show mt-3">
            <i class="fas fa-lock"></i> <strong>Acesso negado.</strong> Esta área é restrita ao administrador superior.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row mt-4">
        <!-- Menu de Administração -->
        <div class="col-md-4">
            <div class="admin-menu">
                <h5 class="mb-3"><?php echo $is_super_admin ? 'Menu Administrativo' : 'Menu do Funcionário'; ?></h5>

                <a href="usuarios.php" class="admin-btn">
                    <i class="fas fa-users"></i> Gerenciar Usuários
                </a>

                <a href="agendamentos.php" class="admin-btn">
                    <i class="fas fa-calendar"></i> Gerenciar Agendamentos
                </a>

                <a href="horarios.php" class="admin-btn">
                    <i class="fas fa-clock"></i> Configurar Horários
                </a>

                <?php if (!$is_super_admin): ?>
                <a href="minha_foto.php" class="admin-btn">
                    <i class="fas fa-camera"></i> Minha Foto de Perfil
                </a>
                <?php endif; ?>

                <?php if ($is_super_admin): ?>
                <a href="servicos.php" class="admin-btn">
                    <i class="fas fa-cut"></i> Gerenciar Serviços e Preços
                </a>
                <a href="produtos.php" class="admin-btn">
                    <i class="fas fa-box-open"></i> Produtos e Estoque
                </a>
                <a href="cupons.php" class="admin-btn">
                    <i class="fas fa-tag"></i> Cupons de Desconto
                </a>
                <a href="fidelidade.php" class="admin-btn">
                    <i class="fas fa-star"></i> Programa de Fidelidade
                </a>
                <a href="pedidos_loja.php" class="admin-btn">
                    <i class="fas fa-shopping-bag"></i> Pedidos da Loja
                    <?php
                    $n_aguardando = $conn->query("SELECT COUNT(*) as n FROM pedidos WHERE status='aguardando'")->fetch_assoc()['n'];
                    if ($n_aguardando > 0): ?>
                    <span class="badge bg-warning text-dark ms-1"><?= $n_aguardando ?></span>
                    <?php endif; ?>
                </a>
                <a href="relatorios.php" class="admin-btn">
                    <i class="fas fa-chart-bar"></i> Relatórios
                </a>
                <?php endif; ?>
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