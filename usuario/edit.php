<?php 
    // Evita cache do navegador
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Pragma: no-cache");
    header("Expires: 0");
    
    include '../config/db.php';
    include '../include/header.php'; 

    // Determina qual identificador usar
    $identifier = null;
    
    if(isset($_GET['identifier'])){
        $identifier = $_GET['identifier'];
    } elseif(isset($_GET['id'])){
        $identifier = $_GET['id'];
    }
    
    if(!$identifier){
        echo '<div class="container mt-4">';
        echo '<div class="alert alert-danger">Erro: Nenhum identificador foi fornecido.</div>';
        echo '<a href="index.php" class="btn btn-primary">Voltar para lista de usuários</a>';
        echo '</div>';
        exit;
    }
    
    // Limpa variáveis para evitar cache
    $nome = $email = $senha = $ativo = $user_id = '';
    
    // Determina se é email ou ID e prepara a consulta
    if(filter_var($identifier, FILTER_VALIDATE_EMAIL)){
        // É um email
        $sql = "SELECT * FROM usuario WHERE email = ?";
    } else {
        // É um ID (mesmo que vazio)
        $sql = "SELECT * FROM usuario WHERE id = ?";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $identifier);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows == 0){
        echo '<div class="container mt-4">';
        echo '<div class="alert alert-warning">Usuário não foi encontrado no banco de dados.</div>';
        echo '<a href="index.php" class="btn btn-primary">Voltar para lista de usuários</a>';
        echo '</div>';
        $stmt->close();
        exit;
    }
    
    $row = $result->fetch_assoc();
    $nome = $row['nome'];
    $email = $row['email'];
    $senha = $row['senha'];
    $ativo = $row['ativo'];
    $user_id = $row['id'];
    
    $stmt->close();
?>

<div class="container mt-4">
    <h2>Editar Usuario</h2>
    <form action="save.php" method="POST" id="formPessoa">
        <div class="mb-2">
            <label>Nome:</label>
            <input type="text" name="nome" value="<?= htmlspecialchars($nome) ?>" class="form-control" required>
        </div>
        <div class="mb-2">
            <label>Email:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" class="form-control" required>
        </div>
        <div class="mb-2">
            <label>Nova Senha:</label>
            <input type="password" id="senha" name="senha" class="form-control" minlength="6" placeholder="Deixe vazio para manter a senha atual">
            <small class="form-text text-muted">Deixe em branco se não quiser alterar a senha</small>
        </div>
        <div class="mb-2">
            <label>Status:</label>
            <input type="radio" name="ativo" value="0" <?= $ativo == '0' ? 'checked' : '' ?>> Bloqueado
            <input type="radio" name="ativo" value="1" <?= $ativo == '1' ? 'checked' : '' ?>> Ativo
        </div>
        <input type="hidden" name="identifier" value="<?= empty($user_id) ? $email : $user_id ?>">
        <input type="hidden" name="original_email" value="<?= htmlspecialchars($email) ?>">
        <button class="btn btn-success" type="submit">Salvar</button>
        <a href="index.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<script src="https://jsuites.net/v5/jsuites.js"></script>
<script src="../js/script.js"></script>
<?php 
    include '../include/footer.php'; 
?>