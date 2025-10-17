<?php 
    include '../config/db.php';
    include '../include/header.php'; 
?>

<div class="container mt-4">
    <h2>Pessoas cadastradas</h2>
    <a href="./create.php" class="btn btn-primary mb-3">Novo</a>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nome</th>
                <th>E-mail</th>
                <th>CPF</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php 
                $sql = "SELECT * FROM pessoa";
                $result = $conn->query($sql);
                while ($row = $result->fetch_assoc()){
                    echo "
                        <tr>
                            <td>{$row['nome']}</td>
                            <td>{$row['email']}</td>
                            <td>{$row['cpf']}</td>
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




<?php include '../include/footer.php'; ?>