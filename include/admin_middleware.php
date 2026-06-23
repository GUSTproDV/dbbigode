<?php
function _iniciarSessao() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Permite acesso a admin E funcionario; redireciona clientes
function verificarAdmin() {
    _iniciarSessao();

    if (!isset($_SESSION['usuario_logado'])) {
        header('Location: ../login.php?erro=acesso_negado');
        exit;
    }

    global $conn;
    $email = $_SESSION['usuario_logado'];
    $stmt  = $conn->prepare("SELECT tipo_usuario FROM usuario WHERE email = ? AND ativo = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$usuario || !in_array($usuario['tipo_usuario'], ['admin', 'funcionario'])) {
        header('Location: ../login.php?erro=acesso_negado_admin');
        exit;
    }

    return true;
}

// Permite acesso APENAS ao admin superior
function verificarSuperAdmin() {
    _iniciarSessao();

    if (!isset($_SESSION['usuario_logado'])) {
        header('Location: ../login.php?erro=acesso_negado');
        exit;
    }

    global $conn;
    $email = $_SESSION['usuario_logado'];
    $stmt  = $conn->prepare("SELECT tipo_usuario FROM usuario WHERE email = ? AND ativo = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $usuario = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$usuario || $usuario['tipo_usuario'] !== 'admin') {
        header('Location: ./index.php?erro=acesso_restrito');
        exit;
    }

    return true;
}

// true se o usuário logado for admin superior
function isSuperAdmin() {
    _iniciarSessao();
    return isset($_SESSION['TIPO_USUARIO']) && $_SESSION['TIPO_USUARIO'] === 'admin';
}

// true se o usuário logado for admin ou funcionario
function isAdmin() {
    _iniciarSessao();
    return isset($_SESSION['TIPO_USUARIO']) && in_array($_SESSION['TIPO_USUARIO'], ['admin', 'funcionario']);
}

// Retorna o tipo do usuário logado
function getTipoAtual() {
    _iniciarSessao();
    return $_SESSION['TIPO_USUARIO'] ?? null;
}
?>
