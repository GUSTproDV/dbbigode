<?php
    session_start(); // Inicia a sessão

    // Adicionando logs para depuração
    error_log('Sessão LOGADO: ' . (isset($_SESSION['LOGADO']) ? $_SESSION['LOGADO'] : 'não definida'));
    error_log('Redirecionando para login se necessário.');

    if (!isset($_SESSION['LOGADO']) || $_SESSION['LOGADO'] !== true) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI']; // Salva a URL atual na sessão
        $_SESSION['login_message'] = "É obrigatório realizar o login para acessar esta página.";
        header('Location: ../index.php'); // Redireciona para a página de login
        exit;
    }

    include '../config/db.php';
    include '../include/header.php';

    // Cancelar agendamento se solicitado
    if (isset($_GET['cancelar']) && is_numeric($_GET['cancelar'])) {
        $id = intval($_GET['cancelar']);
        $stmt = $conn->prepare("DELETE FROM horarios WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        echo "<div style='color:green;text-align:center;'>Agendamento cancelado com sucesso!</div>";
    }
?>
<div class="container mt-4">
    <h2>Horários Agendados</h2>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Corte</th>
                <th>Data</th>
                <th>Hora</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody>
            <?php
                $nomeUsuario = $_SESSION['NOME_USUARIO'];
                $sql = "SELECT id, nome, corte, data, hora FROM horarios WHERE nome = ? ORDER BY data, hora";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $nomeUsuario);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['nome']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['corte']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['data']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['hora']) . "</td>";
                        echo "<td>
                            <a href='listar.php?cancelar=" . $row['id'] . "' 
                               onclick=\"return confirm('Deseja cancelar este agendamento?');\" 
                               class='btn btn-danger btn-sm'>Cancelar</a>
                        </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>Nenhum horário agendado.</td></tr>";
                }

                $stmt->close();
            ?>
        </tbody>
    </table>
</div>
<?php
    include '../include/footer.php';
?>

