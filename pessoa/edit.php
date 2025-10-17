<?php 
    include '../config/db.php';
    include '../include/header.php'; 

    // if(!isset($_SESSION['LOGADO'])){
    //     header('location: ../home');
    //     exit;
    // }

    if(!isset($_GET['id'])){
        echo 'ID não exite';
        exit;
    }

    $id = $_GET['id'];
    $sql = "SELECT * FROM pessoa WHERE id='$id'";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()){
        $nome = $row['nome'];
        $cpf = $row['cpf'];
        $cep = $row['cep'];
        $endereco = $row['endereco'];
        $cidade = $row['cidade'];
        $estado = $row['estado'];
    }
?>

<div class="container mt-4">
    <h2>Cadastrar Pessoa</h2>
    <form action="save.php?id=<?= $_GET['id'] ?>" method="POST" id="formPessoa">
        <div class="mb-2">
            <label>Nome:</label>
            <input type="text" name="nome" value="<?= $nome ?>" class="form-control" required>
        </div>
        <div class="mb-2">
            <label>CPF:</label>
            <input type="text" name="cpf" data-mask='000.000.000-00' value="<?= $cpf ?>" class="form-control" required>
        </div>
        <div class="mb-2">
            <label>CEP:</label>
            <input type="text" id="cep" data-mask='00000-000' name="cep" value="<?= $cep ?>" class="form-control" required>
        </div>
        <div class="mb-2">
            <label>Endereço:</label>
            <input type="text" id="endereco" name="endereco" value="<?= $endereco ?>" class="form-control" required>
        </div>
        <div class="mb-2">
            <label>Cidade:</label>
            <input type="text" id="cidade" name="cidade" value="<?= $cidade ?>" class="form-control" required>
        </div>
        <div class="mb-2">
            <label>Estado:</label>
            <input type="text" id="estado" name="estado" value="<?= $estado ?>" class="form-control" required>
        </div>
        <button class="btn btn-success" type="submit">Salvar</button>
    </form>
</div>

<script src="https://jsuites.net/v5/jsuites.js"></script>
<script src="../js/script.js"></script>
<?php 
    include '../include/footer.php';
?>