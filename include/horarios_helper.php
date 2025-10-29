<?php
/**
 * Funções auxiliares para horários de funcionamento
 */

/**
 * Verifica se a barbearia está aberta em uma data/hora específica
 */
function isAberto($data, $hora, $conn) {
    // Pegar dia da semana (1=segunda, 7=domingo)
    $dia_semana = date('N', strtotime($data));
    
    // Buscar configuração do dia
    $stmt = $conn->prepare("
        SELECT * FROM horarios_funcionamento 
        WHERE dia_semana = ? AND aberto = 1
    ");
    $stmt->bind_param("i", $dia_semana);
    $stmt->execute();
    $result = $stmt->get_result();
    $config = $result->fetch_assoc();
    
    if (!$config) {
        return false; // Fechado neste dia
    }
    
    // Verificar se está dentro do horário de funcionamento
    $hora_check = strtotime($hora);
    $hora_abertura = strtotime($config['hora_abertura']);
    $hora_fechamento = strtotime($config['hora_fechamento']);
    
    // Verificar horário básico
    if ($hora_check < $hora_abertura || $hora_check >= $hora_fechamento) {
        return false;
    }
    
    // Verificar se não está no horário de pausa
    if ($config['hora_pausa_inicio'] && $config['hora_pausa_fim']) {
        $pausa_inicio = strtotime($config['hora_pausa_inicio']);
        $pausa_fim = strtotime($config['hora_pausa_fim']);
        
        if ($hora_check >= $pausa_inicio && $hora_check < $pausa_fim) {
            return false; // Está no horário de pausa
        }
    }
    
    return true;
}

/**
 * Gera lista de horários disponíveis para um dia específico
 */
function getHorariosDisponiveis($data, $conn) {
    $horarios = [];
    
    // Pegar dia da semana
    $dia_semana = date('N', strtotime($data));
    
    // Buscar configuração do dia
    $stmt = $conn->prepare("
        SELECT * FROM horarios_funcionamento 
        WHERE dia_semana = ? AND aberto = 1
    ");
    $stmt->bind_param("i", $dia_semana);
    $stmt->execute();
    $result = $stmt->get_result();
    $config = $result->fetch_assoc();
    
    if (!$config) {
        return $horarios; // Vazio se fechado
    }
    
    // Gerar horários de 30 em 30 minutos
    $hora_atual = strtotime($config['hora_abertura']);
    $hora_fim = strtotime($config['hora_fechamento']);
    $pausa_inicio = $config['hora_pausa_inicio'] ? strtotime($config['hora_pausa_inicio']) : null;
    $pausa_fim = $config['hora_pausa_fim'] ? strtotime($config['hora_pausa_fim']) : null;
    
    while ($hora_atual < $hora_fim) {
        // Verificar se não está no horário de pausa
        $skip = false;
        if ($pausa_inicio && $pausa_fim) {
            if ($hora_atual >= $pausa_inicio && $hora_atual < $pausa_fim) {
                $skip = true;
            }
        }
        
        if (!$skip) {
            $horarios[] = date('H:i:s', $hora_atual);
        }
        
        // Próximo horário (30 minutos)
        $hora_atual += 1800; // 30 minutos em segundos
    }
    
    return $horarios;
}

/**
 * Busca agendamentos existentes para uma data
 */
function getAgendamentosData($data, $conn) {
    $stmt = $conn->prepare("SELECT hora FROM horarios WHERE data = ?");
    $stmt->bind_param("s", $data);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $agendados = [];
    while ($row = $result->fetch_assoc()) {
        $agendados[] = $row['hora'];
    }
    
    return $agendados;
}

/**
 * Filtra horários disponíveis removendo os já agendados
 */
function getHorariosLivres($data, $conn) {
    $todos_horarios = getHorariosDisponiveis($data, $conn);
    $agendados = getAgendamentosData($data, $conn);
    
    // Remover horários já agendados
    $livres = array_diff($todos_horarios, $agendados);
    
    return array_values($livres); // Re-indexar array
}

/**
 * Verifica se um horário específico está disponível
 */
function isHorarioDisponivel($data, $hora, $conn) {
    // Verificar se está no horário de funcionamento
    if (!isAberto($data, $hora, $conn)) {
        return false;
    }
    
    // Verificar se já está agendado
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM horarios WHERE data = ? AND hora = ?");
    $stmt->bind_param("ss", $data, $hora);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'] == 0;
}

/**
 * Formata horário para exibição
 */
function formatarHorario($hora) {
    return date('H:i', strtotime($hora));
}

/**
 * Formata data para exibição
 */
function formatarData($data) {
    setlocale(LC_TIME, 'pt_BR.UTF-8');
    return strftime('%A, %d de %B de %Y', strtotime($data));
}

/**
 * Obtém status de funcionamento para um dia
 */
function getStatusDia($data, $conn) {
    $dia_semana = date('N', strtotime($data));
    
    $stmt = $conn->prepare("
        SELECT nome_dia, aberto, hora_abertura, hora_fechamento, 
               hora_pausa_inicio, hora_pausa_fim 
        FROM horarios_funcionamento 
        WHERE dia_semana = ?
    ");
    $stmt->bind_param("i", $dia_semana);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}
?>