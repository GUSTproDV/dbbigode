<?php
header('Content-Type: text/html; charset=utf-8');
session_start(); // Inicia a sessão

    // Adicionando logs para depuração
    error_log('Sessão LOGADO: ' . (isset($_SESSION['LOGADO']) ? $_SESSION['LOGADO'] : 'não definida'));
    error_log('Redirecionando para login se necessário.');

    if (!isset($_SESSION['LOGADO']) || $_SESSION['LOGADO'] !== true) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI']; // Salva a URL atual na sessão
        $_SESSION['login_message'] = "É obrigatório realizar o login para acessar esta página.";
        header('Location: ../login.php'); // Redireciona para a página de login
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
        echo "<div class='alert alert-success alert-dismissible fade show' style='max-width: 600px; margin: 20px auto;'>
                <i class='fas fa-check-circle'></i> Agendamento cancelado com sucesso!
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
    }
?>

<style>
    .meus-horarios-container {
        max-width: 1200px;
        margin: 30px auto;
        padding: 0 20px;
    }

    .meus-horarios-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .meus-horarios-header h2 {
        color: #8d6742;
        font-weight: bold;
        font-size: 2rem;
        margin-bottom: 10px;
    }

    .meus-horarios-header p {
        color: #666;
        font-size: 1rem;
    }

    .horarios-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .horario-card {
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        padding: 20px;
        transition: transform 0.3s, box-shadow 0.3s;
        border-left: 5px solid #8d6742;
    }

    .horario-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    .horario-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }

    .horario-status {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: bold;
    }

    .status-pendente {
        background: #fff3cd;
        color: #856404;
    }

    .status-realizado {
        background: #d4edda;
        color: #155724;
    }

    .status-cancelado {
        background: #f8d7da;
        color: #721c24;
    }

    .horario-info {
        margin-bottom: 15px;
    }

    .info-row {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
        color: #555;
    }

    .info-row i {
        width: 25px;
        color: #8d6742;
        font-size: 1.1rem;
    }

    .info-label {
        font-weight: 600;
        margin-right: 8px;
        color: #333;
    }

    .info-value {
        color: #666;
    }

    .horario-acoes {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }

    .btn-cancelar {
        flex: 1;
        background: #dc3545;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 10px;
        font-weight: bold;
        cursor: pointer;
        transition: background 0.3s;
    }

    .btn-cancelar:hover {
        background: #c82333;
    }

    .sem-horarios {
        text-align: center;
        padding: 60px 20px;
        background: #f8f9fa;
        border-radius: 15px;
        margin: 20px 0;
    }

    .sem-horarios i {
        font-size: 4rem;
        color: #ccc;
        margin-bottom: 20px;
    }

    .sem-horarios h4 {
        color: #666;
        margin-bottom: 10px;
    }

    .sem-horarios p {
        color: #999;
        margin-bottom: 20px;
    }

    .btn-agendar {
        display: inline-block;
        background: #8d6742;
        color: #fff;
        padding: 12px 30px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: bold;
        transition: background 0.3s;
    }

    .btn-agendar:hover {
        background: #6b4f2e;
        color: #fff;
    }

    @media (max-width: 768px) {
        .horarios-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="meus-horarios-container">
    <div class="meus-horarios-header">
        <h2><i class="fas fa-calendar-check"></i> Meus Horários</h2>
        <p>Gerencie seus agendamentos</p>
    </div>

    <div class="horarios-grid">
        <?php
            $nomeUsuario = $_SESSION['NOME_USUARIO'];
            $sql = "SELECT id, nome, corte, data, hora, status FROM horarios WHERE nome = ? ORDER BY data DESC, hora DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $nomeUsuario);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $status = $row['status'] ?? 'pendente';
                    $statusClass = 'status-' . $status;
                    $statusLabel = ucfirst($status);
                    
                    // Formata a data
                    $dataFormatada = date('d/m/Y', strtotime($row['data']));
                    $diaSemana = strftime('%A', strtotime($row['data']));
                    
                    // Formata a hora
                    $horaFormatada = date('H:i', strtotime($row['hora']));
                    
                    echo "<div class='horario-card'>";
                    echo "  <div class='horario-card-header'>";
                    echo "    <span class='horario-status $statusClass'>$statusLabel</span>";
                    echo "    <span style='color: #999; font-size: 0.9rem;'>#" . $row['id'] . "</span>";
                    echo "  </div>";
                    echo "  <div class='horario-info'>";
                    echo "    <div class='info-row'>";
                    echo "      <i class='fas fa-cut'></i>";
                    echo "      <span class='info-label'>Corte:</span>";
                    echo "      <span class='info-value'>" . htmlspecialchars($row['corte']) . "</span>";
                    echo "    </div>";
                    echo "    <div class='info-row'>";
                    echo "      <i class='fas fa-calendar'></i>";
                    echo "      <span class='info-label'>Data:</span>";
                    echo "      <span class='info-value'>$dataFormatada</span>";
                    echo "    </div>";
                    echo "    <div class='info-row'>";
                    echo "      <i class='fas fa-clock'></i>";
                    echo "      <span class='info-label'>Horário:</span>";
                    echo "      <span class='info-value'>$horaFormatada</span>";
                    echo "    </div>";
                    echo "  </div>";
                    
                    if ($status === 'pendente') {
                        echo "  <div class='horario-acoes'>";
                        echo "    <a href='listar.php?cancelar=" . $row['id'] . "' ";
                        echo "       onclick=\"return confirm('Deseja realmente cancelar este agendamento?');\" ";
                        echo "       class='btn-cancelar'>";
                        echo "      <i class='fas fa-times'></i> Cancelar Agendamento";
                        echo "    </a>";
                        echo "  </div>";
                    }
                    
                    echo "</div>";
                }
            } else {
                echo "<div class='sem-horarios' style='grid-column: 1 / -1;'>";
                echo "  <i class='fas fa-calendar-times'></i>";
                echo "  <h4>Nenhum horário agendado</h4>";
                echo "  <p>Você ainda não possui agendamentos.</p>";
                echo "  <a href='agendar.php' class='btn-agendar'>";
                echo "    <i class='fas fa-plus'></i> Agendar Horário";
                echo "  </a>";
                echo "</div>";
            }

            $stmt->close();
        ?>
    </div>
</div>
<?php
    include '../include/footer.php';
?>

