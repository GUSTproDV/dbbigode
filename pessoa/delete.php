<?php 
    include '../config/db.php';
    if(isset($_GET['id'])){
        $id = $_GET['id'];
        $sql = $conn->prepare("DELETE FROM pessoa WHERE id = ?");
        $sql->bind_param('i', $id);
        $sql->execute();
        $sql->close();
    }
    header('location: index.php');
    exit;
?>