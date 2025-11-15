<?php
header('Content-Type: text/html; charset=utf-8');
include '../config/db.php';
    include '../include/header.php'; 
?>

<div class="container mt-4">
    <h2>Atualizar Pessoa</h2>
    <form action="save.php" method="POST" id="formPessoa">
        <div class="mb-2">
            <label>Nome:</label>
            <input type="text" name="nome" class="form-control" required>
        </div>
        <div class="mb-2">
            <label>CPF:</label>
            <input type="text" data-mask='000.000.000-00' name="cpf" class="form-control" required>
        </div>
        <div class="mb-2">
            <label>Email:</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-2">
            <label>CEP:</label>
            <input type="text" id="cep" data-mask='00000-000' name="cep" class="form-control" required>
        </div>
        <div class="mb-2">
            <label>Endereço:</label>
            <input type="text" id="endereco" name="endereco" class="form-control" required>
        </div>
        <div class="mb-2">
            <label>Cidade:</label>
            <input type="text" id="cidade" name="cidade" class="form-control" required>
        </div>
        <div class="mb-2">
            <label>Estado:</label>
            <input type="text" id="estado" name="estado" class="form-control" required>
        </div>
        <button class="btn btn-success" type="submit">Salvar</button>
    </form>
</div>

<script src="https://jsuites.net/v5/jsuites.js"></script>
<script src="../js/script.js"></script>

<?php 
    include '../include/footer.php'; 
?>