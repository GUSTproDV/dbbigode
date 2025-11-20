<?php
header('Content-Type: text/html; charset=utf-8');
$msg_class = '';
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include './config/db.php';
    
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    // Validações
    if (empty($nome) || empty($email) || empty($senha) || empty($confirmar_senha)) {
        $msg_class = 'alert-danger';
        $msg = 'Todos os campos são obrigatórios!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg_class = 'alert-danger';
        $msg = 'E-mail inválido!';
    } elseif (strlen($senha) < 6) {
        $msg_class = 'alert-danger';
        $msg = 'A senha deve ter no mínimo 6 caracteres!';
    } elseif ($senha !== $confirmar_senha) {
        $msg_class = 'alert-danger';
        $msg = 'As senhas não coincidem!';
    } else {
        // Verificar se o e-mail já existe
        $stmt = $conn->prepare("SELECT id FROM usuario WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $msg_class = 'alert-danger';
            $msg = 'Este e-mail já está cadastrado!';
        } else {
            // Criar novo usuário
            $senha_hash = md5($senha);
            $user_id = sprintf('%s', bin2hex(random_bytes(16))); // Gera um ID único
            
            $stmt = $conn->prepare("INSERT INTO usuario (id, nome, email, senha, tipo_usuario, ativo) VALUES (?, ?, ?, ?, 'cliente', 1)");
            $stmt->bind_param("ssss", $user_id, $nome, $email, $senha_hash);
            
            if ($stmt->execute()) {
                $msg_class = 'alert-success';
                $msg = 'Conta criada com sucesso! Redirecionando...';
                
                // Auto-login após registro
                session_start();
                $_SESSION['LOGADO'] = TRUE;
                $_SESSION['NOME_USUARIO'] = $nome;
                $_SESSION['usuario_logado'] = $email;
                $_SESSION['TIPO_USUARIO'] = 'cliente';
                
                header('refresh:2;url=./home/index.php');
            } else {
                $msg_class = 'alert-danger';
                $msg = 'Erro ao criar conta. Tente novamente.';
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta - Barbearia</title>
    <link rel="stylesheet" href="./assets/bootstrap.min.css">
</head>
<body>
    <form method="POST" action="">
        <h2>Criar Conta</h2>
        <?php if($msg != ''): ?>
            <div class="alert <?= $msg_class ?>"><?= $msg ?></div>
        <?php endif; ?>
        
        <div class="form-floating mb-3">
            <input type="text" name="nome" class="form-control" id="floatingNome" placeholder="Seu nome" required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
            <label for="floatingNome">Nome Completo</label>
        </div>
        
        <div class="form-floating mb-3">
            <input type="email" name="email" class="form-control" id="floatingEmail" placeholder="email@exemplo.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            <label for="floatingEmail">E-mail</label>
        </div>
        
        <div class="form-floating mb-3">
            <input type="password" name="senha" class="form-control" id="floatingPassword" placeholder="Senha" required minlength="6">
            <label for="floatingPassword">Senha (mínimo 6 caracteres)</label>
        </div>
        
        <div class="form-floating mb-3">
            <input type="password" name="confirmar_senha" class="form-control" id="floatingConfirmarSenha" placeholder="Confirmar senha" required minlength="6">
            <label for="floatingConfirmarSenha">Confirmar Senha</label>
        </div>
        
        <input type="submit" value="Criar Conta" class="btn btn-lg btn-dark"/>
        
        <div style="text-align: center; margin-top: 20px;">
            <p style="color: #fffbe6; margin-bottom: 10px;">Já tem uma conta?</p>
            <a href="login.php" class="btn btn-outline-light" style="width: 100%; border-color: #8d6742; color: #fffbe6;">
                Fazer Login
            </a>
        </div>
    </form>
   
    <style>
        body {
            background: linear-gradient(120deg, #1a1a1a 60%, #8d6742 90%, #fffbe6 100%);
            min-height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', Arial, Helvetica, sans-serif; 
        }
        form {
            width: 400px;
            margin: auto;
            margin-top: 5%;
            border: 2px solid #8d6742;
            border-radius: 24px;
            background: rgba(26,26,26,0.98);
            box-shadow: 0 8px 32px rgba(141,103,66,0.18), 0 2px 12px rgba(255,255,255,0.08);
            padding: 32px;
            text-align: center;
        }
        .btn-dark {
            width: 100%;
            margin: 15px 0 0;
            background: linear-gradient(90deg, #8d6742 60%, #fffbe6 100%);
            color: #fffbe6;
            font-weight: bold;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
        }
        .btn-dark:hover {
            background: linear-gradient(90deg, #fffbe6 60%, #8d6742 100%);
            color: #1a1a1a;
            box-shadow: 0 4px 16px rgba(141,103,66,0.18);
        }
        .btn-outline-light:hover {
            background: rgba(141,103,66,0.2);
        }
        h2 {
            font-size: 2rem;
            color: #fffbe6;
            text-shadow: 2px 2px 8px #8d6742;
            font-weight: bold;
            margin-bottom: 20px;
        }
        .alert {
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .form-floating {
            text-align: left;
        }
        .form-control {
            background: rgba(255,255,255,0.95);
            border: 1px solid #8d6742;
        }
        .form-control:focus {
            background: rgba(255,255,255,1);
            border-color: #8d6742;
            box-shadow: 0 0 0 0.2rem rgba(141,103,66,0.25);
        }
    </style>
</body>
</html>
