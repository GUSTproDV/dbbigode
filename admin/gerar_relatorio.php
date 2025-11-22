<?php
// Desabilitar qualquer output de erro
error_reporting(0);
ini_set('display_errors', 0);

// Iniciar buffer de saída
ob_start();

// Conexão com banco sem includes que possam ter headers
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$dbname = 'dbbigode';
$conn = new mysqli($host, $user, $pass, $dbname);
if($conn->connect_error){
    ob_end_clean();
    die('Erro na conexão com banco de dados');
}
$conn->set_charset('utf8mb4');

require_once('../vendor/autoload.php');

// Verificação de admin
session_start();
if (!isset($_SESSION['usuario_logado'])) {
    ob_end_clean();
    die('Acesso negado. Faça login como administrador.');
}

$email = $_SESSION['usuario_logado'];
$sql = "SELECT tipo_usuario FROM usuario WHERE email = ? AND ativo = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    ob_end_clean();
    die('Usuário não encontrado ou inativo.');
}

$user = $result->fetch_assoc();
if (($user['tipo_usuario'] ?? 'cliente') !== 'admin') {
    ob_end_clean();
    die('Acesso negado. Apenas administradores podem gerar relatórios.');
}

// Receber parâmetros
$tipo = $_GET['tipo'] ?? '';
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-t');

if (empty($tipo)) {
    ob_end_clean();
    die('Tipo de relatório não especificado');
}

// Limpar buffer antes de gerar PDF
ob_end_clean();

// Criar instância do PDF
class MYPDF extends TCPDF {
    public function Header() {
        $this->SetFont('helvetica', 'B', 16);
        $this->Cell(0, 15, 'DB Bigode - Barbearia', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln();
        $this->SetFont('helvetica', '', 10);
        $this->Cell(0, 5, 'Relatório Gerencial', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln(10);
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Gerado em: ' . date('d/m/Y H:i:s') . ' | Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetCreator('DB Bigode');
$pdf->SetAuthor('Sistema DB Bigode');
$pdf->SetTitle('Relatório - DB Bigode');
$pdf->SetMargins(15, 30, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, 25);
$pdf->AddPage();

// Formatar datas para exibição
$data_inicio_fmt = date('d/m/Y', strtotime($data_inicio));
$data_fim_fmt = date('d/m/Y', strtotime($data_fim));

switch($tipo) {
    case 'cortes_frequentes':
        gerarRelatorioCortes($pdf, $conn, $data_inicio, $data_fim, $data_inicio_fmt, $data_fim_fmt);
        break;
    case 'clientes_frequentes':
        gerarRelatorioClientes($pdf, $conn, $data_inicio, $data_fim, $data_inicio_fmt, $data_fim_fmt);
        break;
    case 'disponibilidade':
        gerarRelatorioDisponibilidade($pdf, $conn, $data_inicio, $data_fim, $data_inicio_fmt, $data_fim_fmt);
        break;
    default:
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Tipo de relatório inválido', 0, 1);
}

$pdf->Output('relatorio_' . $tipo . '_' . date('Ymd') . '.pdf', 'I');

// ==================== FUNÇÕES DE GERAÇÃO ====================

function gerarRelatorioCortes($pdf, $conn, $data_inicio, $data_fim, $data_inicio_fmt, $data_fim_fmt) {
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Relatório: Cortes Mais Frequentes', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 8, "Período: {$data_inicio_fmt} até {$data_fim_fmt}", 0, 1, 'L');
    $pdf->Ln(5);
    
    // Buscar dados
    $sql = "SELECT 
                corte,
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'realizado' THEN 1 END) as realizados,
                COUNT(CASE WHEN status = 'cancelado' THEN 1 END) as cancelados,
                COUNT(CASE WHEN status = 'pendente' THEN 1 END) as pendentes
            FROM horarios 
            WHERE data BETWEEN ? AND ?
            AND corte IS NOT NULL AND corte != ''
            GROUP BY corte 
            ORDER BY total DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $data_inicio, $data_fim);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Tabela
    $pdf->SetFillColor(141, 103, 66);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 10);
    
    $pdf->Cell(80, 8, 'Serviço', 1, 0, 'L', 1);
    $pdf->Cell(25, 8, 'Total', 1, 0, 'C', 1);
    $pdf->Cell(25, 8, 'Realizados', 1, 0, 'C', 1);
    $pdf->Cell(25, 8, 'Cancelados', 1, 0, 'C', 1);
    $pdf->Cell(25, 8, 'Pendentes', 1, 1, 'C', 1);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 9);
    
    $total_geral = 0;
    $total_realizados = 0;
    $total_cancelados = 0;
    $total_pendentes = 0;
    $tem_dados = false;
    
    while ($row = $result->fetch_assoc()) {
        // Validar se o nome do serviço não está vazio
        $nome_servico = trim($row['corte'] ?? '');
        if (empty($nome_servico)) {
            continue; // Pula registros vazios
        }
        
        $tem_dados = true;
        $pdf->Cell(80, 7, $nome_servico, 1, 0, 'L');
        $pdf->Cell(25, 7, $row['total'], 1, 0, 'C');
        $pdf->Cell(25, 7, $row['realizados'], 1, 0, 'C');
        $pdf->Cell(25, 7, $row['cancelados'], 1, 0, 'C');
        $pdf->Cell(25, 7, $row['pendentes'], 1, 1, 'C');
        
        $total_geral += $row['total'];
        $total_realizados += $row['realizados'];
        $total_cancelados += $row['cancelados'];
        $total_pendentes += $row['pendentes'];
    }
    
    // Se não há dados, mostrar mensagem
    if (!$tem_dados) {
        $pdf->SetFont('helvetica', 'I', 9);
        $pdf->Cell(180, 7, 'Nenhum serviço encontrado no período', 1, 1, 'C');
    }
    
    // Totais
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->Cell(80, 7, 'TOTAL', 1, 0, 'R', 1);
    $pdf->Cell(25, 7, $total_geral, 1, 0, 'C', 1);
    $pdf->Cell(25, 7, $total_realizados, 1, 0, 'C', 1);
    $pdf->Cell(25, 7, $total_cancelados, 1, 0, 'C', 1);
    $pdf->Cell(25, 7, $total_pendentes, 1, 1, 'C', 1);
    
    // Análise
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 8, 'Análise:', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 9);
    if ($total_geral > 0) {
        $taxa_realizacao = round(($total_realizados / $total_geral) * 100, 1);
        $taxa_cancelamento = round(($total_cancelados / $total_geral) * 100, 1);
        
        $pdf->MultiCell(0, 5, "• Total de agendamentos no período: {$total_geral}", 0, 'L');
        $pdf->MultiCell(0, 5, "• Taxa de realização: {$taxa_realizacao}%", 0, 'L');
        $pdf->MultiCell(0, 5, "• Taxa de cancelamento: {$taxa_cancelamento}%", 0, 'L');
    } else {
        $pdf->Cell(0, 5, 'Nenhum agendamento encontrado no período.', 0, 1, 'L');
    }
}

function gerarRelatorioClientes($pdf, $conn, $data_inicio, $data_fim, $data_inicio_fmt, $data_fim_fmt) {
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Relatório: Clientes Mais Frequentes', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 8, "Período: {$data_inicio_fmt} até {$data_fim_fmt}", 0, 1, 'L');
    $pdf->Ln(5);
    
    // Buscar dados
    $sql = "SELECT 
                h.nome,
                COUNT(*) as total_agendamentos,
                COUNT(CASE WHEN h.status = 'realizado' THEN 1 END) as realizados,
                COUNT(CASE WHEN h.status = 'cancelado' THEN 1 END) as cancelados,
                GROUP_CONCAT(DISTINCT h.corte SEPARATOR ', ') as servicos,
                MIN(h.data) as primeira_visita,
                MAX(h.data) as ultima_visita
            FROM horarios h
            WHERE h.data BETWEEN ? AND ?
            GROUP BY h.nome 
            ORDER BY total_agendamentos DESC
            LIMIT 30";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $data_inicio, $data_fim);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Tabela
    $pdf->SetFillColor(141, 103, 66);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 9);
    
    $pdf->Cell(50, 8, 'Cliente', 1, 0, 'L', 1);
    $pdf->Cell(20, 8, 'Total', 1, 0, 'C', 1);
    $pdf->Cell(20, 8, 'Realiz.', 1, 0, 'C', 1);
    $pdf->Cell(20, 8, 'Cancel.', 1, 0, 'C', 1);
    $pdf->Cell(30, 8, 'Primeira', 1, 0, 'C', 1);
    $pdf->Cell(30, 8, 'Última', 1, 1, 'C', 1);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 8);
    
    $total_clientes = 0;
    $total_agendamentos_geral = 0;
    
    while ($row = $result->fetch_assoc()) {
        $pdf->Cell(50, 6, utf8_decode(substr($row['nome'], 0, 25)), 1, 0, 'L');
        $pdf->Cell(20, 6, $row['total_agendamentos'], 1, 0, 'C');
        $pdf->Cell(20, 6, $row['realizados'], 1, 0, 'C');
        $pdf->Cell(20, 6, $row['cancelados'], 1, 0, 'C');
        $pdf->Cell(30, 6, date('d/m/Y', strtotime($row['primeira_visita'])), 1, 0, 'C');
        $pdf->Cell(30, 6, date('d/m/Y', strtotime($row['ultima_visita'])), 1, 1, 'C');
        
        $total_clientes++;
        $total_agendamentos_geral += $row['total_agendamentos'];
    }
    
    // Análise
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 8, 'Análise:', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 9);
    if ($total_clientes > 0) {
        $media_agendamentos = round($total_agendamentos_geral / $total_clientes, 1);
        
        $pdf->MultiCell(0, 5, "• Total de clientes únicos no período: {$total_clientes}", 0, 'L');
        $pdf->MultiCell(0, 5, "• Total de agendamentos: {$total_agendamentos_geral}", 0, 'L');
        $pdf->MultiCell(0, 5, "• Média de agendamentos por cliente: {$media_agendamentos}", 0, 'L');
    } else {
        $pdf->Cell(0, 5, 'Nenhum cliente encontrado no período.', 0, 1, 'L');
    }
}

function gerarRelatorioDisponibilidade($pdf, $conn, $data_inicio, $data_fim, $data_inicio_fmt, $data_fim_fmt) {
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Relatório: Disponibilidade de Agenda', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 8, "Período: {$data_inicio_fmt} até {$data_fim_fmt}", 0, 1, 'L');
    $pdf->Ln(5);
    
    // 1. Disponibilidade por dia da semana
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, '1. Ocupação por Dia da Semana', 0, 1, 'L');
    $pdf->Ln(2);
    
    $sql_dia_semana = "SELECT 
                        DAYNAME(data) as dia_semana,
                        DAYOFWEEK(data) as dia_num,
                        COUNT(*) as total_agendamentos
                      FROM horarios 
                      WHERE data BETWEEN ? AND ?
                      GROUP BY dia_semana, dia_num
                      ORDER BY dia_num";
    
    $stmt = $conn->prepare($sql_dia_semana);
    $stmt->bind_param("ss", $data_inicio, $data_fim);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $dias_pt = [
        'Sunday' => 'Domingo',
        'Monday' => 'Segunda-feira',
        'Tuesday' => 'Terça-feira',
        'Wednesday' => 'Quarta-feira',
        'Thursday' => 'Quinta-feira',
        'Friday' => 'Sexta-feira',
        'Saturday' => 'Sábado'
    ];
    
    $pdf->SetFillColor(141, 103, 66);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 10);
    
    $pdf->Cell(100, 8, 'Dia da Semana', 1, 0, 'L', 1);
    $pdf->Cell(40, 8, 'Agendamentos', 1, 1, 'C', 1);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 9);
    
    $total_geral = 0;
    $tem_dados = false;
    
    while ($row = $result->fetch_assoc()) {
        // Traduzir o nome do dia
        $dia_semana_raw = trim($row['dia_semana']);
        $dia_nome = isset($dias_pt[$dia_semana_raw]) ? $dias_pt[$dia_semana_raw] : $dia_semana_raw;
        
        // Validar se o dia tem nome válido
        if (empty($dia_nome) || empty($dia_semana_raw)) {
            continue;
        }
        
        $tem_dados = true;
        $pdf->Cell(100, 7, $dia_nome, 1, 0, 'L');
        $pdf->Cell(40, 7, $row['total_agendamentos'], 1, 1, 'C');
        $total_geral += $row['total_agendamentos'];
    }
    
    // Se não há dados, mostrar mensagem
    if (!$tem_dados) {
        $pdf->SetFont('helvetica', 'I', 9);
        $pdf->Cell(140, 7, 'Nenhum agendamento encontrado no período', 1, 1, 'C');
    }
    
    $pdf->Ln(8);
    
    // 2. Horários mais procurados
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, '2. Horários Mais Procurados', 0, 1, 'L');
    $pdf->Ln(2);
    
    $sql_horarios = "SELECT 
                        TIME_FORMAT(hora, '%H:%i') as horario,
                        COUNT(*) as total
                     FROM horarios 
                     WHERE data BETWEEN ? AND ?
                     GROUP BY horario
                     ORDER BY total DESC
                     LIMIT 10";
    
    $stmt = $conn->prepare($sql_horarios);
    $stmt->bind_param("ss", $data_inicio, $data_fim);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $pdf->SetFillColor(141, 103, 66);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 10);
    
    $pdf->Cell(70, 8, 'Horário', 1, 0, 'L', 1);
    $pdf->Cell(40, 8, 'Quantidade', 1, 0, 'C', 1);
    $pdf->Cell(40, 8, '% do Total', 1, 1, 'C', 1);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 9);
    
    while ($row = $result->fetch_assoc()) {
        $percentual = $total_geral > 0 ? round(($row['total'] / $total_geral) * 100, 1) : 0;
        $pdf->Cell(70, 7, $row['horario'], 1, 0, 'L');
        $pdf->Cell(40, 7, $row['total'], 1, 0, 'C');
        $pdf->Cell(40, 7, $percentual . '%', 1, 1, 'C');
    }
    
    // 3. Taxa de ocupação
    $pdf->Ln(8);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, '3. Análise de Ocupação', 0, 1, 'L');
    $pdf->Ln(2);
    
    // Calcular dias úteis no período
    $result_funcionamento = $conn->query("SELECT COUNT(*) as dias_abertos FROM horarios_funcionamento WHERE aberto = 1");
    $dias_config = $result_funcionamento->fetch_assoc()['dias_abertos'];
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5, "• Total de agendamentos no período: {$total_geral}", 0, 'L');
    
    if ($total_geral > 0 && $dias_config > 0) {
        $media_dia = round($total_geral / $dias_config, 1);
        $pdf->MultiCell(0, 5, "• Média de agendamentos por dia: {$media_dia}", 0, 'L');
    }
    
    $pdf->MultiCell(0, 5, "• Dias configurados como abertos: {$dias_config}", 0, 'L');
}
