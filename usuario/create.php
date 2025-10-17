<?php 
    include '../config/db.php';
    include '../include/header.php'; 
?>

<div class="container mt-4">
    <h2>Cadastrar Usuário</h2>
    <form action="save.php" method="POST" id="formPessoa">
        <div class="mb-2">
            <label>Nome:</label>
            <input type="text" name="nome" class="form-control" required>
        </div>
        <div class="mb-2">
            <label>Email:</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-2">
            <label>Senha:</label>
            <input type="password" minlength="6" id="senha" name="senha" class="form-control" required>
        </div>
        <button class="btn btn-success" type="submit">Salvar</button>
    </form>
</div>

<script src="https://jsuites.net/v5/jsuites.js"></script>
<script src="../js/script.js"></script>

<?php 
    include '../include/footer.php'; 
?>