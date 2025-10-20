<?php 
session_start(); // Inicia a sessão
if (!isset($_SESSION['LOGADO']) || $_SESSION['LOGADO'] !== true) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI']; // Salva a URL atual na sessão
    $_SESSION['login_message'] = "É obrigatório realizar o login para acessar esta página.";
    header('Location: ../index.php'); // Redireciona para a página de login
    exit;
}

include '../include/header.php'; 
include '../config/db.php';

// Busca os dados do usuário logado
$nome_usuario = $_SESSION['NOME_USUARIO'];
$sql = "SELECT * FROM usuario WHERE nome = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $nome_usuario);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();

// Busca os agendamentos do usuário
$sql_agendamentos = "SELECT * FROM horarios WHERE nome = ? ORDER BY data DESC, hora DESC";
$stmt_agendamentos = $conn->prepare($sql_agendamentos);
$stmt_agendamentos->bind_param("s", $nome_usuario);
$stmt_agendamentos->execute();
$result_agendamentos = $stmt_agendamentos->get_result();
?>

<div class="container-perfil">
    <div class="perfil-header">
        <h2>Meu Perfil</h2>
        <div class="user-avatar">
            <div class="avatar-circle">
                <?= strtoupper(substr($usuario['nome'], 0, 1)) ?>
            </div>
        </div>
    </div>

    <div class="perfil-content">
        <div class="dados-pessoais">
            <h3>Dados Pessoais</h3>
            <div class="info-grid">
                <div class="info-item">
                    <label>Nome:</label>
                    <span><?= htmlspecialchars($usuario['nome']) ?></span>
                </div>
                <div class="info-item">
                    <label>Email:</label>
                    <span><?= htmlspecialchars($usuario['email']) ?></span>
                </div>
                <div class="info-item">
                    <label>Status:</label>
                    <span class="status <?= $usuario['ativo'] ? 'ativo' : 'inativo' ?>">
                        <?= $usuario['ativo'] ? 'Ativo' : 'Inativo' ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="agendamentos-section">
            <h3>Meus Agendamentos</h3>
            <?php if ($result_agendamentos->num_rows > 0): ?>
                <div class="agendamentos-grid">
                    <?php while ($agendamento = $result_agendamentos->fetch_assoc()): ?>
                        <div class="agendamento-card">
                            <div class="agendamento-header">
                                <h4><?= htmlspecialchars($agendamento['corte']) ?></h4>
                                <span class="data"><?= date('d/m/Y', strtotime($agendamento['data'])) ?></span>
                            </div>
                            <div class="agendamento-info">
                                <span class="hora"><?= htmlspecialchars($agendamento['hora']) ?></span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-agendamentos">
                    <p>Você ainda não tem agendamentos.</p>
                    <a href="../home/cortes.php" class="btn-agendar">Fazer Agendamento</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="acoes-perfil">
            <a href="../home/cortes.php" class="btn btn-primary">Novo Agendamento</a>
            <a href="../home/listar.php" class="btn btn-secondary">Ver Todos os Agendamentos</a>
        </div>
    </div>
</div>

<style>
    body {
        background: linear-gradient(120deg, #1a1a1a 60%, #8d6742 90%, #fffbe6 100%);
        min-height: 100vh;
        margin: 0;
        font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
    }

    .container-perfil {
        max-width: 1000px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .perfil-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .perfil-header h2 {
        color: #fffbe6;
        font-size: 2.5rem;
        margin-bottom: 20px;
        text-shadow: 2px 2px 8px #8d6742;
    }

    .user-avatar {
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
    }

    .avatar-circle {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #8d6742 60%, #fffbe6 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: bold;
        color: #fff;
        box-shadow: 0 4px 16px rgba(141,103,66,0.3);
    }

    .perfil-content {
        display: grid;
        gap: 30px;
    }

    .dados-pessoais, .agendamentos-section {
        background: rgba(255,255,255,0.95);
        border-radius: 16px;
        padding: 30px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        border: 2px solid #8d6742;
    }

    .dados-pessoais h3, .agendamentos-section h3 {
        color: #8d6742;
        font-size: 1.5rem;
        margin-bottom: 20px;
        border-bottom: 2px solid #8d6742;
        padding-bottom: 10px;
    }

    .info-grid {
        display: grid;
        gap: 15px;
    }

    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        border-left: 4px solid #8d6742;
    }

    .info-item label {
        font-weight: bold;
        color: #333;
    }

    .info-item span {
        color: #666;
    }

    .status.ativo {
        color: #28a745;
        font-weight: bold;
    }

    .status.inativo {
        color: #dc3545;
        font-weight: bold;
    }

    .agendamentos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
    }

    .agendamento-card {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        border-left: 4px solid #8d6742;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .agendamento-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(141,103,66,0.2);
    }

    .agendamento-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .agendamento-header h4 {
        color: #8d6742;
        margin: 0;
        font-size: 1.2rem;
    }

    .data {
        background: #8d6742;
        color: white;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 0.9rem;
    }

    .hora {
        font-size: 1.1rem;
        font-weight: bold;
        color: #333;
    }

    .no-agendamentos {
        text-align: center;
        padding: 40px;
        color: #666;
    }

    .btn-agendar {
        display: inline-block;
        background: #8d6742;
        color: white;
        padding: 12px 24px;
        border-radius: 8px;
        text-decoration: none;
        margin-top: 15px;
        transition: background 0.3s;
    }

    .btn-agendar:hover {
        background: #6b4f2e;
        text-decoration: none;
        color: white;
    }

    .acoes-perfil {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-top: 30px;
    }

    .btn {
        padding: 12px 24px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: bold;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
    }

    .btn-primary {
        background: #8d6742;
        color: white;
    }

    .btn-primary:hover {
        background: #6b4f2e;
        text-decoration: none;
        color: white;
    }

    .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background: #545b62;
        text-decoration: none;
        color: white;
    }

    @media (max-width: 768px) {
        .perfil-header h2 {
            font-size: 2rem;
        }

        .avatar-circle {
            width: 60px;
            height: 60px;
            font-size: 1.5rem;
        }

        .dados-pessoais, .agendamentos-section {
            padding: 20px;
        }

        .acoes-perfil {
            flex-direction: column;
            align-items: center;
        }

        .agendamentos-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php 
$stmt_agendamentos->close();
include '../include/footer.php'; 
?>