<?php
include '../config/db.php';

// Determina qual identificador usar
$identifier = null;

if(isset($_GET['identifier'])){
    $identifier = $_GET['identifier'];
} elseif(isset($_GET['id'])){
    $identifier = $_GET['id'];
}

if (!$identifier) {
    echo "Nenhum identificador foi fornecido";
    exit;
}

// Determina se é email ou ID e prepara a consulta
if(filter_var($identifier, FILTER_VALIDATE_EMAIL)){
    // É um email
    $sql = "DELETE FROM usuario WHERE email = ?";
} else {
    // É um ID
    $sql = "DELETE FROM usuario WHERE id = ?";
}

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $identifier);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: index.php");
    exit;
} else {
    echo "Erro ao excluir: " . $stmt->error;
}

$conn->close();
?>