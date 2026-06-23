<?php
/**
 * Funções auxiliares para horários de funcionamento
 */

/**
 * Verifica se a barbearia está aberta em uma data/hora específica
 */
function isAberto($data, $hora, $conn) {
    $dia_semana = date('N', strtotime($data));

    $stmt = $conn->prepare("SELECT * FROM horarios_funcionamento WHERE dia_semana = ? AND aberto = 1");
    if (!$stmt) return false;
    $stmt->bind_param("i", $dia_semana);
    $stmt->execute();
    $config = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$config) return false;

    // Normaliza hora para comparação (HH:MM:SS → timestamp de hoje)
    $h       = strtotime(date('Y-m-d') . ' ' . $hora);
    $abre    = strtotime(date('Y-m-d') . ' ' . $config['hora_abertura']);
    $fecha   = strtotime(date('Y-m-d') . ' ' . $config['hora_fechamento']);

    if ($h < $abre || $h >= $fecha) return false;

    if (!empty($config['hora_pausa_inicio']) && !empty($config['hora_pausa_fim'])) {
        $pi = strtotime(date('Y-m-d') . ' ' . $config['hora_pausa_inicio']);
        $pf = strtotime(date('Y-m-d') . ' ' . $config['hora_pausa_fim']);
        if ($h >= $pi && $h < $pf) return false;
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
 * Busca agendamentos existentes para uma data (opcionalmente por barbeiro)
 */
function getAgendamentosData($data, $conn, $barbeiro = null) {
    if ($barbeiro) {
        $stmt = $conn->prepare("SELECT hora FROM horarios WHERE data = ? AND barbeiro = ?");
        $stmt->bind_param("ss", $data, $barbeiro);
    } else {
        $stmt = $conn->prepare("SELECT hora FROM horarios WHERE data = ?");
        $stmt->bind_param("s", $data);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $agendados = [];
    while ($row = $result->fetch_assoc()) {
        $agendados[] = $row['hora'];
    }
    $stmt->close();
    return $agendados;
}

/**
 * Retorna o total de barbeiros ativos cadastrados
 */
function _totalBarbeiros($conn) {
    $res = $conn->query("SELECT COUNT(*) as total FROM usuario WHERE tipo_usuario = 'funcionario' AND ativo = 1");
    return $res ? (int)$res->fetch_assoc()['total'] : 0;
}

/**
 * Filtra horários disponíveis removendo os já agendados.
 *
 * - Barbeiro específico: bloqueia apenas os slots daquele barbeiro.
 * - Sem preferência: cada barbeiro tem 1 vaga por slot.
 *   O slot só fica indisponível quando TODOS os barbeiros estão ocupados nele.
 *   Se não há barbeiros cadastrados, funciona como 1 vaga global por slot.
 */
function getHorariosLivres($data, $conn, $barbeiro = null) {
    $todos = getHorariosDisponiveis($data, $conn);

    if ($barbeiro) {
        // Isolamento por barbeiro: apenas os slots deste barbeiro são bloqueados
        $agendados = getAgendamentosData($data, $conn, $barbeiro);
        return array_values(array_diff($todos, $agendados));
    }

    // Sem preferência
    $total_barb = _totalBarbeiros($conn);

    if ($total_barb == 0) {
        // Sem barbeiros: comportamento original (1 slot global por horário)
        $agendados = getAgendamentosData($data, $conn, null);
        return array_values(array_diff($todos, $agendados));
    }

    // Com barbeiros: slot disponível se ainda houver barbeiro livre
    $livres = [];
    foreach ($todos as $hora) {
        $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM horarios WHERE data = ? AND hora = ?");
        $stmt->bind_param("ss", $data, $hora);
        $stmt->execute();
        $cnt = (int)$stmt->get_result()->fetch_assoc()['cnt'];
        $stmt->close();
        if ($cnt < $total_barb) {
            $livres[] = $hora;
        }
    }
    return $livres;
}

/**
 * Verifica se um horário específico está disponível para agendamento.
 *
 * - Barbeiro específico: slot livre se este barbeiro não tiver agendamento nele.
 * - Sem preferência: slot válido se ao menos 1 barbeiro estiver livre.
 */
function isHorarioDisponivel($data, $hora, $conn, $barbeiro = null) {
    if (!isAberto($data, $hora, $conn)) {
        return false;
    }

    if ($barbeiro) {
        // Verifica apenas se ESTE barbeiro já tem agendamento no horário
        $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM horarios WHERE data = ? AND hora = ? AND barbeiro = ?");
        $stmt->bind_param("sss", $data, $hora, $barbeiro);
        $stmt->execute();
        $cnt = (int)$stmt->get_result()->fetch_assoc()['cnt'];
        $stmt->close();
        return $cnt === 0;
    }

    // Sem preferência
    $total_barb = _totalBarbeiros($conn);

    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM horarios WHERE data = ? AND hora = ?");
    $stmt->bind_param("ss", $data, $hora);
    $stmt->execute();
    $cnt = (int)$stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    if ($total_barb == 0) {
        return $cnt === 0; // 1 slot global
    }
    return $cnt < $total_barb; // ao menos 1 barbeiro livre
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