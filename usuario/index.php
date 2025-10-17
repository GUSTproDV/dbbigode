<?php 
    include '../config/db.php';
    include '../include/header.php'; 
?>

<div class="container mt-4">
    <h2>Usuários cadastradas</h2>
    <a href="./create.php" class="btn btn-primary mb-3">Novo</a>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php 
                $sql = "SELECT * FROM usuario";
                $result = $conn->query($sql);
                while ($row = $result->fetch_assoc()){
                    $sts = ($row['ativo']) ? 'ativo' : 'bloqueado';  
                    echo "
                        <tr>
                            <td><span class='sts {$sts}'></span></td>
                            <td>{$row['nome']}</td>
                            <td>{$row['email']}</td>
                            <td>
                                <a href='edit.php?id={$row['id']}' class='btn btn-info'>Editar</a>
                                <a href='delete.php?id={$row['id']}' 
                                onclick='return confirm(\"Deseja excluir?\")'
                                class='btn btn-danger'>Excluir</a>
                            </td>
                        </tr>
                    ";
                }
            ?>
        </tbody>
    </table>
</div>


<style>
    .sts {
        width: 20px;
        height: 20px;
        border: 1px #CCC solid;
        display: block;
    }
    .bloqueado {
        background: red;
    }
    .ativo {
        background: green;
    }
</style>


<?php include '../include/footer.php'; ?>