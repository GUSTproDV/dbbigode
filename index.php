<?php
    header('Content-Type: text/html; charset=utf-8');
    
    // Redireciona para o home como página inicial
    session_start();
    header('Location: home/index.php');
    exit;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="./assets/bootstrap.min.css">
</head>
<body>
    <form action="" method="post">
        <h2>Bem-vindo à INVICTUS</h2>
        <?php if($msg != ''): ?>
            <div class="alert <?= $msg_class ?>"><?= $msg ?></div>
        <?php endif; ?>
        <div class="form-floating mb-3">
            <input type="email" name="email" class="form-control" id="floatingEmail" placeholder="email@exemplo.com">
            <label for="floatingEmail">E-mail</label>
        </div>
        <div class="form-floating">
            <input type="password" name="senha" class="form-control" id="floatingPassword" placeholder="Password">
            <label for="floatingPassword">Senha</label>
        </div>
        <input type="submit" value="Entrar" class="btn btn-lg btn-dark"/>
        <div style="text-align: center; margin-top: 20px;">
            <p style="color: #fffbe6; margin-bottom: 10px;">Ainda não tem uma conta?</p>
            <a href="registro.php" class="btn btn-outline-light" style="width: 100%; border-color: #8d6742; color: #fffbe6;">
                Criar Conta
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
            width: 350px;
            margin: auto;
            margin-top: 10%;
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
        .logo {
            width: 80px;
            display: block;
            margin: auto;
            margin-bottom: 20px;
        }
        h2 {
            font-size: 2rem;
            color: #fffbe6;
            text-shadow: 2px 2px 8px #8d6742;
            font-weight: bold;
            margin-bottom: 20px;
        }
    </style>
    <?php
        session_start();
        if (isset($_SESSION['login_message'])) {
            echo "<div style='position:fixed; top:20px; left:50%; transform:translateX(-50%); background-color:red; color:white; padding:10px; border-radius:5px; z-index:1000;'>" . htmlspecialchars($_SESSION['login_message']) . "</div>";
            unset($_SESSION['login_message']); // Remove a mensagem após exibi-la
        }
    ?>
</body>
</html>