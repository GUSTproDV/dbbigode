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
            background: linear-gradient(135deg, #0a0a0a 0%, #052e16 55%, #14532d 100%);
            min-height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
        }
        form {
            width: 400px;
            margin: auto;
            margin-top: 5%;
            border: 2px solid #22c55e;
            border-radius: 24px;
            background: rgba(10, 10, 10, 0.96);
            box-shadow: 0 8px 40px rgba(34, 197, 94, 0.18), 0 2px 12px rgba(0,0,0,0.4);
            padding: 32px;
            text-align: center;
        }
        .btn-dark {
            width: 100%;
            margin: 15px 0 0;
            background: linear-gradient(90deg, #15803d 60%, #22c55e 100%);
            color: #ffffff;
            font-weight: bold;
            border: none;
            box-shadow: 0 2px 8px rgba(34,197,94,0.2);
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
        }
        .btn-dark:hover {
            background: linear-gradient(90deg, #22c55e 0%, #15803d 100%);
            color: #ffffff;
            box-shadow: 0 4px 16px rgba(34,197,94,0.35);
        }
        .btn-outline-light {
            border-color: #22c55e !important;
            color: #4ade80 !important;
        }
        .btn-outline-light:hover {
            background: rgba(34,197,94,0.15) !important;
        }
        h2 {
            font-size: 2rem;
            color: #4ade80;
            text-shadow: 0 0 18px rgba(74, 222, 128, 0.4);
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
            background: rgba(240, 253, 244, 0.95);
            border: 1px solid #22c55e;
            color: #0a0a0a;
        }
        .form-control:focus {
            background: #ffffff;
            border-color: #4ade80;
            box-shadow: 0 0 0 0.2rem rgba(74,222,128,0.25);
        }
        p { color: #d1fae5; }
    </style>
</body>
</html>
