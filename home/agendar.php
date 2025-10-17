<?php
session_start(); // Inicia a sessão
if (!isset($_SESSION['LOGADO']) || $_SESSION['LOGADO'] !== true) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI']; // Salva a URL atual na sessão
    $_SESSION['login_message'] = "É obrigatório realizar o login para acessar esta página.";
    header('Location: ../index.php'); // Redireciona para a página de login
    exit;
}

include '../include/header.php';
include '../config/db.php';

// Pega o corte agendado da URL, se existir
$corte_agendado = isset($_GET['servico']) ? $_GET['servico'] : '';

// Gera os próximos 7 dias a partir da data atual
$dias = [];
for ($i = 0; $i < 7; $i++) {
    $data = date('Y-m-d', strtotime("+$i days"));
    $label = strftime('%A', strtotime($data)); // Nome do dia da semana
    $dias[] = ['label' => ucfirst($label), 'data' => $data];
}   

// Array de horários disponíveis
$horarios = [
    '09:00', '09:30', '10:00', '10:30', '11:00', '11:30',
    '13:00', '13:30', '14:00', '14:30', '15:00', '15:30',
    '16:00', '16:30', '17:00', '17:30', '18:00'
];

// Filtra os horários disponíveis para cada dia
foreach ($dias as &$dia) {
    $data = $dia['data'];
    $sql = "SELECT hora FROM horarios WHERE data = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $data);
    $stmt->execute();
    $result = $stmt->get_result();
    $horarios_agendados = [];
    while ($row = $result->fetch_assoc()) {
        $horarios_agendados[] = $row['hora'];
    }
    $stmt->close();

    // Remove os horários agendados do array de horários disponíveis
    $dia['horarios_disponiveis'] = array_filter($horarios, function($hora) use ($horarios_agendados) {
        return !in_array($hora, $horarios_agendados);
    });
}
?>

<style>
    body {
        font-family: Arial, sans-serif;
        background: linear-gradient(120deg, #1a1a1a 60%, #8d6742 90%, #fffbe6 100%);
        margin: 0;
        padding: 0;
        color: #333;
    }

    .container-agendamento {
        max-width: 1100px;
        margin: 40px auto;
        padding: 0 16px;
    }

    h2 {
        text-align: center;
        font-size: 2rem;
        color: #8d6742;
        margin-bottom: 20px;
    }

    .dias-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 20px;
    }

    .dia-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        padding: 16px;
        text-align: center;
    }

    .dia-titulo {
        font-size: 1.2rem;
        font-weight: bold;
        color: #8d6742;
        margin-bottom: 12px;
    }

    .horarios-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
        gap: 10px;
        justify-content: center;
        transition: max-height 0.3s ease-in-out;
        overflow: hidden;
    }

    .horario-btn {
        background: #8d6742;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 10px 16px;
        cursor: pointer;
        font-size: 1rem;
        transition: background 0.3s;
    }

    .horario-btn:hover {
        background: #6b4f2e;
    }

    .horario-btn.selected {
        background: #d80e00ff; /* Cor diferente para o botão selecionado */
        color: #fff;
        border: 2px solid #8d6742;
        font-weight: bold;
    }

    .ver-mais {
        display: block;
        margin-top: 10px;
        color: #8d6742;
        text-decoration: none;
        font-weight: bold;
    }

    .ver-mais:hover {
        text-decoration: underline;
    }

    form {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        padding: 20px;
    }

    .form-control {
        width: 100%;
        padding: 10px;
        margin-bottom: 10px;
        border: 1px solid #ccc;
        border-radius: 8px;
    }

    .btn-success {
        background: #8d6742;
        color: #fff;
        border: none;
        border-radius: 8px;
        padding: 10px 16px;
        cursor: pointer;
        font-size: 1rem;
        transition: background 0.3s;
    }

    .btn-success:hover {
        background: #6b4f2e;
    }

    .extra-horarios {
        max-height: 0;
        visibility: hidden;
        opacity: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-in-out, opacity 0.3s ease-in-out;
    }

    .extra-horarios.show {
        max-height: 500px; /* Ajuste conforme necessário */
        visibility: visible;
        opacity: 1;
    }
</style>

<div class="container-agendamento">
    <h2>Agende seu horário</h2>
    <div class="dias-grid">
        <?php foreach ($dias as $idx => $dia): ?>
            <div class="dia-card">
                <div class="dia-titulo">
                    <?php
                        setlocale(LC_TIME, 'pt_BR.UTF-8');
                        $dataFormatada = strftime('%d de %B de %Y', strtotime($dia['data']));
                        echo "{$dia['label']}, {$dataFormatada}";
                    ?>
                </div>
                <div class="horarios-grid" id="horarios-<?php echo $idx; ?>">
                    <?php
                        // Exibe os primeiros 6 horários disponíveis
                        $count = 0;
                        foreach ($dia['horarios_disponiveis'] as $h) {
                            if ($count < 6) {
                                echo "<button class='horario-btn' data-dia='{$dia['data']}' data-hora='{$h}'>{$h}</button>";
                            }
                            $count++;
                        }
                    ?>
                    <div class="extra-horarios">
                        <?php
                            $count = 0;
                            foreach ($dia['horarios_disponiveis'] as $h) {
                                if ($count >= 6) {
                                    echo "<button class='horario-btn' data-dia='{$dia['data']}' data-hora='{$h}'>{$h}</button>";
                                }
                                $count++;
                            }
                        ?>
                    </div>
                </div>
                <a href="#" class="ver-mais" data-target="horarios-<?php echo $idx; ?>">VER MAIS</a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Formulário de agendamento -->
<form id="form-agendar" method="post" style="max-width:400px;margin:30px auto;">
    <?php if ($corte_agendado): ?>
        <div class="form-group mb-2">
            <label><strong>Corte escolhido:</strong></label>
            <input type="text" name="corte" value="<?php echo htmlspecialchars($corte_agendado); ?>" readonly class="form-control">
        </div>
    <?php endif; ?>
    <input type="text" name="nome" placeholder="Seu nome" required class="form-control mb-2">
    <input type="hidden" name="data" id="input-data">
    <input type="hidden" name="hora" id="input-hora">
    <button type="submit" class="btn btn-success w-100">Agendar</button>
</form>

<?php
include '../include/footer.php';

// Se o formulário foi enviado, salva no banco
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $corte = $_POST['corte'] ?? '';
    $data = $_POST['data'] ?? '';
    $hora = $_POST['hora'] ?? '';

    if ($nome && $data && $hora) {
        $stmt = $conn->prepare("INSERT INTO horarios (nome, corte, data, hora) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nome, $corte, $data, $hora);
        if ($stmt->execute()) {
            echo "<div style='color:green;text-align:center;'>Agendamento realizado com sucesso!</div>";
        } else {
            echo "<div style='color:red;text-align:center;'>Erro ao agendar!</div>";
        }
        $stmt->close();
    }
}
?>

<script>
    document.querySelectorAll('.horario-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove a classe 'selected' de todos os botões
            document.querySelectorAll('.horario-btn').forEach(b => b.classList.remove('selected'));

            // Adiciona a classe 'selected' ao botão clicado
            btn.classList.add('selected');

            // Define os valores nos campos ocultos
            const data = btn.getAttribute('data-dia');
            const hora = btn.getAttribute('data-hora');
            document.getElementById('input-data').value = data;
            document.getElementById('input-hora').value = hora;
        });
    });

    document.querySelectorAll('.ver-mais').forEach(btn => {
        btn.addEventListener('click', function(event) {
            event.preventDefault(); // Evita o comportamento padrão do link

            // Obtém o alvo do botão
            const targetId = btn.getAttribute('data-target');
            const extraHorarios = document.querySelector(`#${targetId} .extra-horarios`);

            // Alterna a exibição dos horários adicionais
            if (extraHorarios) {
                if (extraHorarios.classList.contains('show')) {
                    extraHorarios.classList.remove('show');
                    btn.innerText = 'VER MAIS';
                } else {
                    extraHorarios.classList.add('show');
                    btn.innerText = 'VER MENOS';
                }
            }
        });
    });
</script>

<?php
// Adicionando log para depuração
error_log('Sessão LOGADO: ' . (isset($_SESSION['LOGADO']) ? $_SESSION['LOGADO'] : 'não definida'));
error_log('URL salva para redirecionamento: ' . $_SERVER['REQUEST_URI']);

// Log temporário para verificar a URL salva na sessão
error_log('Redirect URL saved: ' . $_SERVER['REQUEST_URI']);
?>