<?php
include '../config/db.php';

var_dump($_GET); // Adicione esta linha para depuração

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "DELETE FROM usuario WHERE id='$id'";
    if ($conn->query($sql) === TRUE) {
        header("Location: index.php");
        exit;
    } else {
        echo "Erro ao excluir: " . $conn->error;
    }
} else {
    echo "ID inválido";
}
$conn->close();
?>