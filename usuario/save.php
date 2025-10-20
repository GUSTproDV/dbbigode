<?php

    include '../config/db.php';
    
    if($_SERVER['REQUEST_METHOD'] === 'POST'){

        $identifier = isset($_POST['identifier']) && !empty(trim($_POST['identifier'])) ? trim($_POST['identifier']) : null;
        $original_email = isset($_POST['original_email']) ? trim($_POST['original_email']) : null;
        $nome = trim($_POST['nome']);
        $email = trim($_POST['email']);
        $ativo = $_POST['ativo'] ?? 1;

        // Validações básicas
        if(empty($nome) || empty($email)){
            echo 'Nome e email são obrigatórios';
            exit;
        }

        if($identifier){
            // Atualização - usa email original para encontrar o registro
            $search_field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'id';
            $search_value = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? $original_email : $identifier;
            
            if(!empty($_POST['senha'])){
                // Atualiza com nova senha
                $senha = md5($_POST['senha']);
                $sql = "UPDATE usuario SET nome = ?, email = ?, senha = ?, ativo = ? WHERE $search_field = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssis", $nome, $email, $senha, $ativo, $search_value);
            } else {
                // Atualiza sem alterar a senha
                $sql = "UPDATE usuario SET nome = ?, email = ?, ativo = ? WHERE $search_field = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssis", $nome, $email, $ativo, $search_value);
            }
        } else {
            // Inserção
            if(empty($_POST['senha'])){
                echo 'Senha é obrigatória para novos usuários';
                exit;
            }
            $senha = md5($_POST['senha']);
            $sql = "INSERT INTO usuario (nome, email, senha, ativo) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $nome, $email, $senha, $ativo);
        }
   
        if($stmt->execute()){
            $stmt->close();
            header("Location: index.php");
            exit;
        } else {
            echo 'Erro: ' . $stmt->error;
        }

        $conn->close();
    }
?>