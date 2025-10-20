<?php

    include '../config/db.php';
    
    if($_SERVER['REQUEST_METHOD'] === 'POST'){

        $identifier = isset($_POST['identifier']) && !empty(trim($_POST['identifier'])) ? trim($_POST['identifier']) : null;
        $original_email = isset($_POST['original_email']) ? trim($_POST['original_email']) : null;
        $nome = trim($_POST['nome']);
        $email = trim($_POST['email']);
        $ativo = $_POST['ativo'] ?? 1;
        
        // Debug temporário
        echo "<!-- DEBUG: identifier = " . htmlspecialchars($identifier ?? 'NULL') . " -->";
        echo "<!-- DEBUG: original_email = " . htmlspecialchars($original_email ?? 'NULL') . " -->";
        echo "<!-- DEBUG: senha vazia = " . (empty($_POST['senha']) ? 'SIM' : 'NÃO') . " -->";

        // Validações básicas
        if(empty($nome) || empty($email)){
            echo '<div class="alert alert-danger">Nome e email são obrigatórios</div>';
            echo '<a href="javascript:history.back()" class="btn btn-primary">Voltar</a>';
            exit;
        }
        
        if(!$identifier){
            echo '<div class="alert alert-danger">Erro: Identificador não encontrado</div>';
            echo '<a href="index.php" class="btn btn-primary">Voltar para lista</a>';
            exit;
        }

        if($identifier){
            // Atualização - usa email original para encontrar o registro
            $search_field = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'id';
            $search_value = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? $original_email : $identifier;
            
            echo "<!-- DEBUG: search_field = $search_field, search_value = " . htmlspecialchars($search_value) . " -->";
            
            if(!empty($_POST['senha']) && trim($_POST['senha']) !== ''){
                // Atualiza com nova senha
                $senha = md5(trim($_POST['senha']));
                $sql = "UPDATE usuario SET nome = ?, email = ?, senha = ?, ativo = ? WHERE $search_field = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssis", $nome, $email, $senha, $ativo, $search_value);
                echo "<!-- DEBUG: Atualizando COM nova senha -->";
            } else {
                // Atualiza sem alterar a senha
                $sql = "UPDATE usuario SET nome = ?, email = ?, ativo = ? WHERE $search_field = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssis", $nome, $email, $ativo, $search_value);
                echo "<!-- DEBUG: Atualizando SEM alterar senha -->";
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
            if($stmt->affected_rows > 0){
                echo '<div class="container mt-4">';
                echo '<div class="alert alert-success">Usuário atualizado com sucesso!</div>';
                echo '<a href="index.php" class="btn btn-primary">Voltar para lista</a>';
                echo '</div>';
                $stmt->close();
                // Redireciona após 2 segundos
                echo '<script>setTimeout(function(){ window.location.href="index.php"; }, 2000);</script>';
            } else {
                echo '<div class="container mt-4">';
                echo '<div class="alert alert-warning">Nenhuma alteração foi feita.</div>';
                echo '<a href="index.php" class="btn btn-primary">Voltar para lista</a>';
                echo '</div>';
                $stmt->close();
            }
        } else {
            echo '<div class="container mt-4">';
            echo '<div class="alert alert-danger">Erro ao atualizar: ' . htmlspecialchars($stmt->error) . '</div>';
            echo '<a href="javascript:history.back()" class="btn btn-primary">Voltar</a>';
            echo '</div>';
        }

        $conn->close();
    }
?>