<?php
session_start();
header('Content-Type: text/html; charset=utf-8');

$msg_class = '';
$msg = '';

if (isset($_POST['email'], $_POST['senha'])) {
    if ($_POST['email'] === '' || $_POST['senha'] === '') {
        $msg_class = 'alert-danger';
        $msg = 'E-mail ou senha inválida';
    } else {
        include './config/db.php';

        $email = $_POST['email'];
        $senha = md5($_POST['senha']);

        $stmt = $conn->prepare("SELECT * FROM usuario WHERE email = ? AND senha = ?");
        $stmt->bind_param("ss", $email, $senha);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $msg_class = 'alert-danger';
            $msg = 'E-mail ou Senha Inválida.';
        } else {
            $row = $result->fetch_assoc();
            if ($row['ativo'] == 0) {
                $msg_class = 'alert-warning';
                $msg = 'Usuário bloqueado, entre em contato com o adm.';
            } else {
                $_SESSION['LOGADO']        = true;
                $_SESSION['NOME_USUARIO']  = $row['nome'];
                $_SESSION['usuario_logado'] = $row['email'];
                $_SESSION['TIPO_USUARIO']  = $row['tipo_usuario'] ?? 'cliente';

                if (in_array($_SESSION['TIPO_USUARIO'], ['admin', 'funcionario'])) {
                    header('Location: ./admin/index.php');
                    exit;
                } elseif (isset($_SESSION['redirect_after_login'])) {
                    $redirect = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    header('Location: ' . $redirect);
                    exit;
                } else {
                    header('Location: ./home/index.php');
                    exit;
                }
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
    <title>Login</title>
    <link rel="stylesheet" href="./assets/bootstrap.min.css">
</head>
<body>
    <form action="" method="post">
        <h2>Bem-vindo à INVICTUS</h2>

        <?php if ($msg !== ''): ?>
            <div class="alert <?= $msg_class ?>"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['login_message'])): ?>
            <div class="alert alert-warning"><?= htmlspecialchars($_SESSION['login_message']) ?></div>
            <?php unset($_SESSION['login_message']); ?>
        <?php endif; ?>

        <div class="form-floating mb-3">
            <input type="email" name="email" class="form-control" id="floatingEmail" placeholder="email@exemplo.com" required>
            <label for="floatingEmail">E-mail</label>
        </div>
        <div class="form-floating">
            <input type="password" name="senha" class="form-control" id="floatingPassword" placeholder="Password" required>
            <label for="floatingPassword">Senha</label>
        </div>
        <input type="submit" value="Entrar" class="btn btn-lg btn-dark"/>
        <div style="text-align:center; margin-top:20px;">
            <p>Ainda não tem uma conta?</p>
            <a href="registro.php" class="btn btn-outline-light" style="width:100%;">Criar Conta</a>
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
            width: 350px;
            margin: 10% auto 0;
            border: 2px solid #22c55e;
            border-radius: 24px;
            background: rgba(10,10,10,0.96);
            box-shadow: 0 8px 40px rgba(34,197,94,0.18);
            padding: 32px;
            text-align: center;
        }
        h2 { font-size: 2rem; color: #4ade80; text-shadow: 0 0 18px rgba(74,222,128,0.4); font-weight: bold; margin-bottom: 20px; }
        .btn-dark { width:100%; margin:15px 0 0; background:linear-gradient(90deg,#15803d 60%,#22c55e 100%); color:#fff; font-weight:bold; border:none; transition:background 0.2s,box-shadow 0.2s; }
        .btn-dark:hover { background:linear-gradient(90deg,#22c55e 0%,#15803d 100%); color:#fff; box-shadow:0 4px 16px rgba(34,197,94,0.35); }
        .form-control { background:rgba(240,253,244,0.95); border:1px solid #22c55e; color:#0a0a0a; }
        .form-control:focus { background:#fff; border-color:#4ade80; box-shadow:0 0 0 0.2rem rgba(74,222,128,0.25); }
        label { color:#6b7280; }
        .btn-outline-light { border-color:#22c55e !important; color:#4ade80 !important; }
        .btn-outline-light:hover { background:rgba(34,197,94,0.15) !important; }
        p { color:#d1fae5; }
        .alert { border-radius:12px; }
    </style>
</body>
</html>
