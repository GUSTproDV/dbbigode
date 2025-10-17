<?php

    include '../config/db.php';
    
    if($_SERVER['REQUEST_METHOD'] === 'POST'){

        $id = isset($_POST['id']) && is_numeric($_POST['id']) ? $_POST['id'] : null;
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $ativo = $_POST['ativo'] ?? 1;

        // Só atualiza a senha se foi preenchida
        $senha = !empty($_POST['senha']) ? md5($_POST['senha']) : null;

        if($id){
            $sql = "UPDATE usuario SET
                nome='$nome', 
                email='$email', 
                ativo='$ativo'";
            if ($senha) {
                $sql .= ", senha='$senha'";
            }
            $sql .= " WHERE id='$id'";
        } else {
            $sql = "INSERT INTO usuario (nome, email, senha, ativo)
                VALUES
                ('$nome', '$email', '".md5($_POST['senha'])."', '$ativo')";
        }
   
        if($conn->query($sql) === TRUE){
            header("Location: index.php");
            exit;
        } else {
            echo 'Error: ' . $conn->error;
        }

        $conn->close();
    }
?>