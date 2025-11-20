<?php
// Middleware para verificar se o usuário é administrador
function verificarAdmin() {
    session_start();
    
    // Verifica se está logado
    if (!isset($_SESSION['usuario_logado'])) {
        header('Location: ../login.php?erro=acesso_negado');
        exit;
    }
    
    // Usar conexão global do banco (já incluída)
    global $conn;
    
    $email = $_SESSION['usuario_logado'];
    $sql = "SELECT tipo_usuario FROM usuario WHERE email = ? AND ativo = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    
    // Verifica se é admin
    if (!$usuario || $usuario['tipo_usuario'] !== 'admin') {
        header('Location: ../login.php?erro=acesso_negado_admin');
        exit;
    }
    
    return true;
}

// Função para verificar se o usuário logado é admin (sem redirecionamento)
function isAdmin() {
    if (!isset($_SESSION['usuario_logado'])) {
        return false;
    }
    
    // Usar conexão global do banco (já incluída)
    global $conn;
    
    $email = $_SESSION['usuario_logado'];
    $sql = "SELECT tipo_usuario FROM usuario WHERE email = ? AND ativo = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    
    return $usuario && $usuario['tipo_usuario'] === 'admin';
}
?>