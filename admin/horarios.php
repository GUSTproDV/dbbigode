<?php
// Incluir conexão do banco primeiro
include_once('../config/db.php');

// Depois verificar se é admin
require_once('../include/admin_middleware.php');
verificarAdmin();

$sucesso = '';
$erro = '';

// Processar alterações nos horários
if ($_POST) {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'salvar_horarios') {
        $conn->begin_transaction();
        
        try {
            // Atualizar cada dia da semana
            for ($dia = 1; $dia <= 7; $dia++) {
                $aberto = isset($_POST["aberto_$dia"]) ? 1 : 0;
                $hora_abertura = $_POST["hora_abertura_$dia"] ?? null;
                $hora_fechamento = $_POST["hora_fechamento_$dia"] ?? null;
                $hora_pausa_inicio = $_POST["hora_pausa_inicio_$dia"] ?? null;
                $hora_pausa_fim = $_POST["hora_pausa_fim_$dia"] ?? null;
                
                // Se fechado, limpar horários
                if (!$aberto) {
                    $hora_abertura = $hora_fechamento = $hora_pausa_inicio = $hora_pausa_fim = null;
                }
                
                // Se não tem pausa, limpar horários de pausa
                if (empty($hora_pausa_inicio) || empty($hora_pausa_fim)) {
                    $hora_pausa_inicio = $hora_pausa_fim = null;
                }
                
                $stmt = $conn->prepare("
                    UPDATE horarios_funcionamento 
                    SET aberto = ?, hora_abertura = ?, hora_fechamento = ?, 
                        hora_pausa_inicio = ?, hora_pausa_fim = ?
                    WHERE dia_semana = ?
                ");
                $stmt->bind_param("issssi", $aberto, $hora_abertura, $hora_fechamento, 
                                  $hora_pausa_inicio, $hora_pausa_fim, $dia);
                $stmt->execute();
            }
            
            $conn->commit();
            $sucesso = "Horários de funcionamento atualizados com sucesso!";
            
        } catch (Exception $e) {
            $conn->rollback();
            $erro = "Erro ao salvar horários: " . $e->getMessage();
        }
    }
    
    if ($acao === 'resetar_padrao') {
        $conn->begin_transaction();
        
        try {
            // Resetar para configuração padrão
            $horarios_padrao = [
                1 => ['Segunda-feira', 1, '09:00:00', '18:00:00', '12:00:00', '13:00:00'],
                2 => ['Terça-feira', 1, '09:00:00', '18:00:00', '12:00:00', '13:00:00'],
                3 => ['Quarta-feira', 1, '09:00:00', '18:00:00', '12:00:00', '13:00:00'],
                4 => ['Quinta-feira', 1, '09:00:00', '18:00:00', '12:00:00', '13:00:00'],
                5 => ['Sexta-feira', 1, '09:00:00', '18:00:00', '12:00:00', '13:00:00'],
                6 => ['Sábado', 1, '09:00:00', '17:00:00', null, null],
                7 => ['Domingo', 0, null, null, null, null]
            ];
            
            foreach ($horarios_padrao as $dia => $dados) {
                $stmt = $conn->prepare("
                    UPDATE horarios_funcionamento 
                    SET aberto = ?, hora_abertura = ?, hora_fechamento = ?, 
                        hora_pausa_inicio = ?, hora_pausa_fim = ?
                    WHERE dia_semana = ?
                ");
                $stmt->bind_param("issssi", $dados[1], $dados[2], $dados[3], $dados[4], $dados[5], $dia);
                $stmt->execute();
            }
            
            $conn->commit();
            $sucesso = "Horários resetados para configuração padrão!";
            
        } catch (Exception $e) {
            $conn->rollback();
            $erro = "Erro ao resetar horários: " . $e->getMessage();
        }
    }
}

// Buscar horários atuais
$result = $conn->query("SELECT * FROM horarios_funcionamento ORDER BY dia_semana");
$horarios = [];
while ($row = $result->fetch_assoc()) {
    $horarios[$row['dia_semana']] = $row;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Horários de Funcionamento - Admin</title>
    <link href="../assets/bootstrap.min.css" rel="stylesheet">
    <style>
        .admin-header {
            background: linear-gradient(135deg, #17a2b8, #007bff);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
        }
        .horarios-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .dia-row {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #007bff;
        }
        .dia-row.fechado {
            border-left-color: #dc3545;
            opacity: 0.7;
        }
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #2196F3;
        }
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        .horario-input {
            width: 100px;
        }
    </style>
</head>
<body style="background-color: #f8f9fa;">

<div class="admin-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1><i class="fas fa-clock"></i> Configurar Horários de Funcionamento</h1>
                <p>Defina os dias e horários que a barbearia irá atender</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="index.php" class="btn btn-light">
                    <i class="fas fa-arrow-left"></i> Voltar ao Painel
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <?php if ($sucesso): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo $sucesso; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($erro): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?php echo $erro; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST" id="horariosForm">
        <input type="hidden" name="acao" value="salvar_horarios">
        
        <div class="horarios-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5><i class="fas fa-calendar-week"></i> Horários por Dia da Semana</h5>
                <div>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                    <button type="button" class="btn btn-outline-secondary ms-2" onclick="resetarPadrao()">
                        <i class="fas fa-undo"></i> Resetar Padrão
                    </button>
                </div>
            </div>

            <?php foreach ($horarios as $dia_num => $horario): ?>
            <div class="dia-row <?php echo $horario['aberto'] ? '' : 'fechado'; ?>" id="dia_<?php echo $dia_num; ?>">
                <div class="row align-items-center">
                    <!-- Nome do dia e switch -->
                    <div class="col-md-2">
                        <h6 class="mb-2"><?php echo $horario['nome_dia']; ?></h6>
                        <label class="switch">
                            <input type="checkbox" name="aberto_<?php echo $dia_num; ?>" 
                                   <?php echo $horario['aberto'] ? 'checked' : ''; ?>
                                   onchange="toggleDia(<?php echo $dia_num; ?>)">
                            <span class="slider"></span>
                        </label>
                        <small class="d-block mt-1 status-text">
                            <?php echo $horario['aberto'] ? 'Aberto' : 'Fechado'; ?>
                        </small>
                    </div>

                    <!-- Horários -->
                    <div class="col-md-10 horarios-inputs" <?php echo $horario['aberto'] ? '' : 'style="display:none;"'; ?>>
                        <div class="row">
                            <!-- Abertura e Fechamento -->
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-6">
                                        <label class="form-label small">Abertura</label>
                                        <input type="time" class="form-control horario-input" 
                                               name="hora_abertura_<?php echo $dia_num; ?>"
                                               value="<?php echo $horario['hora_abertura']; ?>"
                                               <?php echo $horario['aberto'] ? 'required' : ''; ?>>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small">Fechamento</label>
                                        <input type="time" class="form-control horario-input" 
                                               name="hora_fechamento_<?php echo $dia_num; ?>"
                                               value="<?php echo $horario['hora_fechamento']; ?>"
                                               <?php echo $horario['aberto'] ? 'required' : ''; ?>>
                                    </div>
                                </div>
                            </div>

                            <!-- Pausa/Almoço -->
                            <div class="col-md-6">
                                <div class="row">
                                    <div class="col-6">
                                        <label class="form-label small">Pausa Início</label>
                                        <input type="time" class="form-control horario-input" 
                                               name="hora_pausa_inicio_<?php echo $dia_num; ?>"
                                               value="<?php echo $horario['hora_pausa_inicio']; ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small">Pausa Fim</label>
                                        <input type="time" class="form-control horario-input" 
                                               name="hora_pausa_fim_<?php echo $dia_num; ?>"
                                               value="<?php echo $horario['hora_pausa_fim']; ?>">
                                    </div>
                                </div>
                                <small class="text-muted">Opcional - horário de almoço/pausa</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </form>

   

    <!-- Visualização Resumida -->
    <div class="horarios-card">
        <h5><i class="fas fa-eye"></i> Resumo dos Horários</h5>
        <div class="row">
            <?php foreach ($horarios as $dia_num => $horario): ?>
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card <?php echo $horario['aberto'] ? 'border-success' : 'border-danger'; ?>">
                    <div class="card-body p-3">
                        <h6 class="card-title"><?php echo $horario['nome_dia']; ?></h6>
                        <?php if ($horario['aberto']): ?>
                            <p class="card-text small mb-1">
                                <i class="fas fa-clock text-success"></i> 
                                <?php echo date('H:i', strtotime($horario['hora_abertura'])); ?> - 
                                <?php echo date('H:i', strtotime($horario['hora_fechamento'])); ?>
                            </p>
                            <?php if ($horario['hora_pausa_inicio']): ?>
                            <p class="card-text small mb-0 text-muted">
                                <i class="fas fa-coffee"></i> Pausa: 
                                <?php echo date('H:i', strtotime($horario['hora_pausa_inicio'])); ?> - 
                                <?php echo date('H:i', strtotime($horario['hora_pausa_fim'])); ?>
                            </p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="card-text small text-danger">
                                <i class="fas fa-times-circle"></i> Fechado
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Form para resetar -->
<form method="POST" id="resetForm" style="display: none;">
    <input type="hidden" name="acao" value="resetar_padrao">
</form>

<script src="https://kit.fontawesome.com/a076d05399.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleDia(dia) {
    const diaRow = document.getElementById(`dia_${dia}`);
    const checkbox = diaRow.querySelector('input[type="checkbox"]');
    const horariosInputs = diaRow.querySelector('.horarios-inputs');
    const statusText = diaRow.querySelector('.status-text');
    const requiredInputs = diaRow.querySelectorAll('input[name*="abertura"], input[name*="fechamento"]');
    
    if (checkbox.checked) {
        // Aberto
        diaRow.classList.remove('fechado');
        horariosInputs.style.display = 'block';
        statusText.textContent = 'Aberto';
        requiredInputs.forEach(input => input.required = true);
    } else {
        // Fechado
        diaRow.classList.add('fechado');
        horariosInputs.style.display = 'none';
        statusText.textContent = 'Fechado';
        requiredInputs.forEach(input => {
            input.required = false;
            input.value = '';
        });
        // Limpar também horários de pausa
        const pausaInputs = diaRow.querySelectorAll('input[name*="pausa"]');
        pausaInputs.forEach(input => input.value = '');
    }
}

function resetarPadrao() {
    if (confirm('Deseja resetar todos os horários para a configuração padrão?\n\nIsso irá:\n- Segunda a Sexta: 09:00-18:00 (pausa 12:00-13:00)\n- Sábado: 09:00-17:00\n- Domingo: Fechado')) {
        document.getElementById('resetForm').submit();
    }
}

// Validação do formulário
document.getElementById('horariosForm').addEventListener('submit', function(e) {
    let valid = true;
    const diasAbertos = document.querySelectorAll('input[type="checkbox"]:checked');
    
    if (diasAbertos.length === 0) {
        alert('Atenção: Você deve ter pelo menos um dia aberto para funcionamento!');
        e.preventDefault();
        return;
    }
    
    diasAbertos.forEach(checkbox => {
        const dia = checkbox.name.replace('aberto_', '');
        const abertura = document.querySelector(`input[name="hora_abertura_${dia}"]`).value;
        const fechamento = document.querySelector(`input[name="hora_fechamento_${dia}"]`).value;
        
        if (!abertura || !fechamento) {
            alert(`Por favor, preencha os horários de abertura e fechamento para todos os dias abertos.`);
            valid = false;
            e.preventDefault();
            return;
        }
        
        if (abertura >= fechamento) {
            alert(`O horário de abertura deve ser anterior ao fechamento.`);
            valid = false;
            e.preventDefault();
            return;
        }
    });
});
</script>
</body>
</html>