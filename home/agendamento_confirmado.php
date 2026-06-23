<?php
header('Content-Type: text/html; charset=utf-8');
session_start();

if (!isset($_SESSION['LOGADO']) || $_SESSION['LOGADO'] !== true) {
    header('Location: ../login.php');
    exit;
}

include '../config/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

// Busca o agendamento com preço do serviço
$stmt = $conn->prepare("
    SELECT h.id, h.nome, h.corte, h.barbeiro, h.data, h.hora, h.status,
           s.preco, s.duracao, s.descricao
    FROM horarios h
    LEFT JOIN servicos s ON s.nome = h.corte
    WHERE h.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$ag = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Garante que o agendamento pertence ao usuário logado
if (!$ag || $ag['nome'] !== ($_SESSION['NOME_USUARIO'] ?? '')) {
    header('Location: index.php');
    exit;
}

$dias_semana = ['Sunday'=>'Domingo','Monday'=>'Segunda-feira','Tuesday'=>'Terça-feira',
                'Wednesday'=>'Quarta-feira','Thursday'=>'Quinta-feira','Friday'=>'Sexta-feira','Saturday'=>'Sábado'];
$dia_nome  = $dias_semana[date('l', strtotime($ag['data']))] ?? '';
$data_fmt  = $dia_nome . ', ' . date('d/m/Y', strtotime($ag['data']));
$hora_fmt  = date('H:i', strtotime($ag['hora']));
$preco_fmt = $ag['preco'] !== null ? 'R$ ' . number_format($ag['preco'], 2, ',', '.') : 'Não informado';
$duracao   = $ag['duracao'] ? $ag['duracao'] . ' min' : '--';
$barbeiro  = $ag['barbeiro'] ?: 'Sem preferência';
$corte     = $ag['corte'] ?: 'Não informado';

include '../include/header.php';
?>

<style>
body {
    background: linear-gradient(155deg, #0a1a10 0%, #0d2b1a 45%, #071510 100%);
    min-height: 100vh;
    font-family: 'Segoe UI', Arial, sans-serif;
}
.confirm-wrapper {
    min-height: calc(100vh - 64px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 32px 16px 48px;
}
.confirm-card {
    background: #1c1c2a;
    border-radius: 28px;
    padding: 36px 28px 32px;
    width: 100%;
    max-width: 440px;
    box-shadow: 0 32px 80px rgba(0,0,0,0.7), inset 0 1px 0 rgba(255,255,255,0.06);
    text-align: center;
}
.check-circle {
    width: 80px; height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #14532d, #22c55e);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 20px;
    box-shadow: 0 8px 28px rgba(34,197,94,0.35);
    font-size: 2.4rem;
}
.confirm-title {
    color: #fff;
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 6px;
}
.confirm-subtitle {
    color: rgba(255,255,255,0.45);
    font-size: 0.9rem;
    margin-bottom: 28px;
}
.detalhes {
    background: #252535;
    border-radius: 18px;
    overflow: hidden;
    margin-bottom: 24px;
    text-align: left;
}
.detalhe-row {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 18px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}
.detalhe-row:last-child { border-bottom: none; }
.detalhe-icon {
    width: 38px; height: 38px;
    border-radius: 10px;
    background: rgba(34,197,94,0.12);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.detalhe-label {
    color: rgba(255,255,255,0.4);
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 2px;
}
.detalhe-valor {
    color: #fff;
    font-size: 0.97rem;
    font-weight: 600;
}
.detalhe-valor.preco { color: #4ade80; font-size: 1.1rem; }
.badge-pendente {
    display: inline-block;
    background: rgba(251,191,36,0.15);
    color: #fbbf24;
    border: 1px solid rgba(251,191,36,0.3);
    border-radius: 20px;
    padding: 3px 12px;
    font-size: 0.82rem;
    font-weight: 600;
}
.acoes { display: flex; flex-direction: column; gap: 10px; }
.btn-primary-green {
    display: block;
    background: linear-gradient(90deg, #15803d 55%, #22c55e 100%);
    color: #fff;
    padding: 14px;
    border-radius: 14px;
    text-decoration: none;
    font-weight: 700;
    font-size: 1rem;
    transition: background 0.2s, transform 0.15s;
    box-shadow: 0 4px 18px rgba(21,128,61,0.4);
}
.btn-primary-green:hover {
    background: linear-gradient(90deg, #14532d 55%, #15803d 100%);
    color: #fff;
    transform: translateY(-2px);
}
.btn-secondary-outline {
    display: block;
    background: transparent;
    color: rgba(255,255,255,0.55);
    padding: 12px;
    border-radius: 14px;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.95rem;
    border: 1px solid rgba(255,255,255,0.12);
    transition: color 0.2s, border-color 0.2s;
}
.btn-secondary-outline:hover {
    color: #fff;
    border-color: rgba(255,255,255,0.3);
}
</style>

<div class="confirm-wrapper">
    <div class="confirm-card">
        <div class="check-circle">✅</div>
        <h2 class="confirm-title">Agendamento Confirmado!</h2>
        <p class="confirm-subtitle">Seu horário foi reservado com sucesso.</p>

        <div class="detalhes">
            <div class="detalhe-row">
                <div class="detalhe-icon">✂️</div>
                <div>
                    <div class="detalhe-label">Serviço</div>
                    <div class="detalhe-valor"><?php echo htmlspecialchars($corte); ?></div>
                </div>
            </div>
            <div class="detalhe-row">
                <div class="detalhe-icon">💰</div>
                <div>
                    <div class="detalhe-label">Preço</div>
                    <div class="detalhe-valor preco"><?php echo $preco_fmt; ?></div>
                </div>
            </div>
            <?php if ($ag['duracao']): ?>
            <div class="detalhe-row">
                <div class="detalhe-icon">⏱️</div>
                <div>
                    <div class="detalhe-label">Duração estimada</div>
                    <div class="detalhe-valor"><?php echo $duracao; ?></div>
                </div>
            </div>
            <?php endif; ?>
            <div class="detalhe-row">
                <div class="detalhe-icon">💈</div>
                <div>
                    <div class="detalhe-label">Barbeiro</div>
                    <div class="detalhe-valor"><?php echo htmlspecialchars($barbeiro); ?></div>
                </div>
            </div>
            <div class="detalhe-row">
                <div class="detalhe-icon">📅</div>
                <div>
                    <div class="detalhe-label">Data</div>
                    <div class="detalhe-valor"><?php echo $data_fmt; ?></div>
                </div>
            </div>
            <div class="detalhe-row">
                <div class="detalhe-icon">🕐</div>
                <div>
                    <div class="detalhe-label">Horário</div>
                    <div class="detalhe-valor"><?php echo $hora_fmt; ?></div>
                </div>
            </div>
            <div class="detalhe-row">
                <div class="detalhe-icon">📋</div>
                <div>
                    <div class="detalhe-label">Status</div>
                    <div class="detalhe-valor"><span class="badge-pendente">⏳ Pendente</span></div>
                </div>
            </div>
        </div>

        <div class="acoes">
            <a href="listar.php" class="btn-primary-green">Ver Meus Agendamentos</a>
            <a href="agendar.php" class="btn-secondary-outline">Fazer outro agendamento</a>
        </div>
    </div>
</div>

<?php include '../include/footer.php'; ?>
