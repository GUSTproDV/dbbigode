<?php
header('Content-Type: text/html; charset=utf-8');
session_start(); // Inicia a sessão
if (!isset($_SESSION['LOGADO']) || $_SESSION['LOGADO'] !== true) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI']; // Salva a URL atual na sessão
    $_SESSION['login_message'] = "É obrigatório realizar o login para acessar esta página.";
    header('Location: ../login.php'); // Redireciona para a página de login
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

// Processamento do formulário de atualização de perfil
$mensagem = '';
$tipo_mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_perfil'])) {
    $novo_nome = trim($_POST['nome']);
    $novo_email = trim($_POST['email']);
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $email_original = $_POST['email_original'];

    // Validações
    if (empty($novo_nome) || empty($novo_email)) {
        $mensagem = 'Nome e email são obrigatórios.';
        $tipo_mensagem = 'danger';
    } elseif (!filter_var($novo_email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = 'Email inválido.';
        $tipo_mensagem = 'danger';
    } elseif (empty($senha_atual)) {
        $mensagem = 'Senha atual é obrigatória para confirmar as alterações.';
        $tipo_mensagem = 'danger';
    } elseif (md5($senha_atual) !== $usuario['senha']) {
        $mensagem = 'Senha atual incorreta.';
        $tipo_mensagem = 'danger';
    } elseif (!empty($nova_senha) && strlen($nova_senha) < 6) {
        $mensagem = 'Nova senha deve ter pelo menos 6 caracteres.';
        $tipo_mensagem = 'danger';
    } elseif (!empty($nova_senha) && $nova_senha !== $confirmar_senha) {
        $mensagem = 'Nova senha e confirmação não coincidem.';
        $tipo_mensagem = 'danger';
    } else {
        // Verifica se o email já existe (se foi alterado)
        if ($novo_email !== $email_original) {
            $sql_check = "SELECT id FROM usuario WHERE email = ? AND email != ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("ss", $novo_email, $email_original);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            
            if ($result_check->num_rows > 0) {
                $mensagem = 'Este email já está sendo usado por outro usuário.';
                $tipo_mensagem = 'danger';
                $stmt_check->close();
            } else {
                $stmt_check->close();
                // Prossegue com a atualização
                $atualizar_perfil = true;
            }
        } else {
            $atualizar_perfil = true;
        }
        
        if (isset($atualizar_perfil)) {
            // Monta a query de atualização
            if (!empty($nova_senha)) {
                $senha_hash = md5($nova_senha);
                $sql_update = "UPDATE usuario SET nome = ?, email = ?, senha = ? WHERE email = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("ssss", $novo_nome, $novo_email, $senha_hash, $email_original);
            } else {
                $sql_update = "UPDATE usuario SET nome = ?, email = ? WHERE email = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("sss", $novo_nome, $novo_email, $email_original);
            }
            
            if ($stmt_update->execute()) {
                // Atualiza a sessão com o novo nome
                $_SESSION['NOME_USUARIO'] = $novo_nome;
                
                $mensagem = 'Perfil atualizado com sucesso!';
                $tipo_mensagem = 'success';
                
                // Recarrega os dados do usuário
                $sql = "SELECT * FROM usuario WHERE email = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $novo_email);
                $stmt->execute();
                $result = $stmt->get_result();
                $usuario = $result->fetch_assoc();
                $stmt->close();
                
            } else {
                $mensagem = 'Erro ao atualizar perfil: ' . $stmt_update->error;
                $tipo_mensagem = 'danger';
            }
            $stmt_update->close();
        }
    }
}

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

    <?php if (!empty($mensagem)): ?>
        <div class="alert alert-<?= $tipo_mensagem ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($mensagem) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">&times;</button>
        </div>
    <?php endif; ?>

    <div class="perfil-content">
        <div class="dados-pessoais">
            <div class="dados-header">
                <h3>Dados Pessoais</h3>
                <button id="btn-editar-perfil" class="btn-editar-perfil">
                    <i class="edit-icon">✏️</i> Editar Perfil
                </button>
            </div>
            
            <div class="info-grid" id="info-display">
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
            
            <!-- Formulário de edição (inicialmente oculto) -->
            <form id="form-editar-perfil" class="form-editar-perfil" method="POST" style="display: none;">
                <div class="form-group">
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="senha_atual">Senha Atual:</label>
                    <input type="password" id="senha_atual" name="senha_atual" class="form-control" placeholder="Digite sua senha atual">
                </div>
                <div class="form-group">
                    <label for="nova_senha">Nova Senha:</label>
                    <input type="password" id="nova_senha" name="nova_senha" class="form-control" placeholder="Digite nova senha (deixe vazio para manter)">
                </div>
                <div class="form-group">
                    <label for="confirmar_senha">Confirmar Nova Senha:</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" class="form-control" placeholder="Confirme a nova senha">
                </div>
                <div class="form-actions">
                    <button type="submit" name="atualizar_perfil" class="btn btn-primary">Salvar Alterações</button>
                    <button type="button" id="btn-cancelar-edicao" class="btn btn-secondary">Cancelar</button>
                </div>
                <input type="hidden" name="email_original" value="<?= htmlspecialchars($usuario['email']) ?>">
            </form>
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

    /* Estilos para edição de perfil */
    .dados-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .btn-editar-perfil {
        background: #8d6742;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: background 0.3s;
    }

    .btn-editar-perfil:hover {
        background: #6b4f2e;
    }

    .form-editar-perfil {
        margin-top: 20px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 12px;
        border: 2px solid #8d6742;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        font-weight: bold;
        color: #333;
        margin-bottom: 5px;
    }

    .form-control {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }

    .form-control:focus {
        border-color: #8d6742;
        outline: none;
        box-shadow: 0 0 0 2px rgba(141, 103, 66, 0.2);
    }

    .form-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
    }

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid transparent;
        border-radius: 8px;
        position: relative;
    }

    .alert-success {
        color: #155724;
        background-color: #d4edda;
        border-color: #c3e6cb;
    }

    .alert-danger {
        color: #721c24;
        background-color: #f8d7da;
        border-color: #f5c6cb;
    }

    .btn-close {
        position: absolute;
        top: 10px;
        right: 15px;
        background: none;
        border: none;
        font-size: 1.2rem;
        cursor: pointer;
        color: inherit;
    }

    @media (max-width: 768px) {
        .dados-header {
            flex-direction: column;
            gap: 10px;
            align-items: stretch;
        }

        .form-actions {
            flex-direction: column;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnEditarPerfil = document.getElementById('btn-editar-perfil');
    const formEditarPerfil = document.getElementById('form-editar-perfil');
    const infoDisplay = document.getElementById('info-display');
    const btnCancelarEdicao = document.getElementById('btn-cancelar-edicao');
    
    // Botão para mostrar formulário de edição
    btnEditarPerfil.addEventListener('click', function() {
        infoDisplay.style.display = 'none';
        formEditarPerfil.style.display = 'block';
        btnEditarPerfil.style.display = 'none';
    });
    
    // Botão para cancelar edição
    btnCancelarEdicao.addEventListener('click', function() {
        formEditarPerfil.style.display = 'none';
        infoDisplay.style.display = 'grid';
        btnEditarPerfil.style.display = 'flex';
        
        // Reset do formulário
        formEditarPerfil.reset();
        
        // Restaura valores originais
        document.getElementById('nome').value = '<?= htmlspecialchars($usuario['nome']) ?>';
        document.getElementById('email').value = '<?= htmlspecialchars($usuario['email']) ?>';
    });
    
    // Validação de confirmação de senha
    const novaSenha = document.getElementById('nova_senha');
    const confirmarSenha = document.getElementById('confirmar_senha');
    
    confirmarSenha.addEventListener('input', function() {
        if (novaSenha.value && confirmarSenha.value) {
            if (novaSenha.value !== confirmarSenha.value) {
                confirmarSenha.setCustomValidity('As senhas não coincidem');
            } else {
                confirmarSenha.setCustomValidity('');
            }
        }
    });
    
    // Validação do formulário
    formEditarPerfil.addEventListener('submit', function(e) {
        const senhaAtual = document.getElementById('senha_atual').value;
        const novaSenhaVal = novaSenha.value;
        const confirmarSenhaVal = confirmarSenha.value;
        
        if (!senhaAtual) {
            e.preventDefault();
            alert('Senha atual é obrigatória para confirmar as alterações.');
            return;
        }
        
        if (novaSenhaVal && novaSenhaVal.length < 6) {
            e.preventDefault();
            alert('Nova senha deve ter pelo menos 6 caracteres.');
            return;
        }
        
        if (novaSenhaVal && novaSenhaVal !== confirmarSenhaVal) {
            e.preventDefault();
            alert('Nova senha e confirmação não coincidem.');
            return;
        }
    });
    
    // Auto-fechar alertas
    const alertas = document.querySelectorAll('.alert');
    alertas.forEach(function(alerta) {
        const btnClose = alerta.querySelector('.btn-close');
        if (btnClose) {
            btnClose.addEventListener('click', function() {
                alerta.style.display = 'none';
            });
        }
        
        // Auto-fechar após 5 segundos
        setTimeout(function() {
            alerta.style.opacity = '0';
            setTimeout(function() {
                alerta.style.display = 'none';
            }, 300);
        }, 5000);
    });
});
</script>

<?php 
$stmt_agendamentos->close();
include '../include/footer.php'; 
?>