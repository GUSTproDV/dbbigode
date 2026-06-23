<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
if (!isset($_SESSION['LOGADO']) || $_SESSION['LOGADO'] !== true) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    $_SESSION['login_message'] = "É obrigatório realizar o login para acessar esta página.";
    header('Location: ../login.php');
    exit;
}

// DB e helper ANTES do header.php para poder redirecionar no POST
include '../config/db.php';
include '../include/horarios_helper.php';

// Processamento do agendamento (deve ocorrer antes de qualquer output HTML)
$erro_agendamento = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = $_SESSION['NOME_USUARIO'] ?? '';
    $corte    = $_POST['corte']    ?? '';
    $barbeiro = $_POST['barbeiro'] ?? '';
    $data     = $_POST['data']     ?? '';
    $hora     = $_POST['hora']     ?? '';

    if ($nome && $data && $hora) {
        $hora_f   = strlen($hora) === 5 ? $hora . ':00' : $hora;
        $barb_chk = $barbeiro ?: null;

        if (!isHorarioDisponivel($data, $hora_f, $conn, $barb_chk)) {
            $erro_agendamento = 'horario_indisponivel';
        } else {
            $stmt = $conn->prepare("INSERT INTO horarios (nome, corte, barbeiro, data, hora) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $nome, $corte, $barbeiro, $data, $hora_f);
            if ($stmt->execute()) {
                $novo_id = $conn->insert_id;
                $stmt->close();
                header('Location: agendamento_confirmado.php?id=' . $novo_id);
                exit;
            }
            $erro_agendamento = 'erro_bd';
            $stmt->close();
        }
    } else {
        $erro_agendamento = 'dados_incompletos';
    }
}

// Pré-selecionados via URL
$corte_pre    = $_GET['servico']  ?? '';
$barbeiro_pre = $_GET['barbeiro'] ?? '';

// Buscar serviços
$result_srv = $conn->query("SELECT * FROM servicos WHERE ativo = 1 ORDER BY ordem ASC, nome ASC");
$servicos = [];
if ($result_srv) {
    while ($r = $result_srv->fetch_assoc()) $servicos[] = $r;
}

// Buscar barbeiros
$result_barb = $conn->query("SELECT nome, foto FROM usuario WHERE tipo_usuario = 'funcionario' AND ativo = 1 ORDER BY nome ASC");
$lista_barbeiros = [];
if ($result_barb) {
    while ($r = $result_barb->fetch_assoc()) $lista_barbeiros[] = $r;
}

include '../include/header.php';

// Gerar disponibilidade para os próximos 7 dias, por barbeiro
setlocale(LC_TIME, 'portuguese', 'Portuguese_Brazil', 'ptb');
$nomes_dias = [0=>'Domingo',1=>'Segunda-feira',2=>'Terça-feira',3=>'Quarta-feira',4=>'Quinta-feira',5=>'Sexta-feira',6=>'Sábado'];

function buildDias($conn, $barbeiro = null) {
    global $nomes_dias;
    $dias = [];
    $data_atual = strtotime('today');
    for ($i = 0; $i < 7; $i++) {
        $ts   = strtotime("+$i days", $data_atual);
        $data = date('Y-m-d', $ts);
        $dias[] = ['label' => $nomes_dias[date('w', $ts)], 'data' => $data];
    }
    foreach ($dias as &$dia) {
        $status = getStatusDia($dia['data'], $conn);
        if (!$status || !$status['aberto']) {
            $dia['horarios_disponiveis'] = [];
            $dia['status'] = 'fechado';
            continue;
        }
        $dia['horarios_disponiveis'] = getHorariosLivres($dia['data'], $conn, $barbeiro);
        $dia['status'] = 'aberto';
    }
    unset($dia);
    return $dias;
}

// Disponibilidade: chave '' = sem preferência, demais = nome do barbeiro
$dados_barbeiros = ['' => buildDias($conn, null)];
foreach ($lista_barbeiros as $barb) {
    $dados_barbeiros[$barb['nome']] = buildDias($conn, $barb['nome']);
}
?>

<style>
* { box-sizing: border-box; }
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: linear-gradient(155deg, #0a1a10 0%, #0d2b1a 45%, #071510 100%);
    min-height: 100vh;
    margin: 0;
}
.page-wrapper {
    min-height: calc(100vh - 64px);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px 16px 48px;
}

/* ── CARD ── */
.agendamento-card {
    background: #1c1c2a;
    border-radius: 28px;
    padding: 28px 22px 24px;
    width: 100%;
    max-width: 400px;
    box-shadow: 0 32px 80px rgba(0,0,0,0.7), inset 0 1px 0 rgba(255,255,255,0.06);
}
.card-title {
    text-align: center;
    color: #fff;
    font-size: 1.3rem;
    font-weight: 700;
    margin: 0 0 6px;
}
.step-indicator {
    display: flex;
    justify-content: center;
    gap: 8px;
    margin-bottom: 22px;
}
.step-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: rgba(255,255,255,0.15);
    transition: background 0.3s;
}
.step-dot.active { background: #22c55e; }

/* ── STEPS ── */
.step { display: none; }
.step.active { display: block; }

/* ── SERVIÇOS (step 1) ── */
.servicos-lista { display: flex; flex-direction: column; gap: 10px; max-height: 340px; overflow-y: auto; scrollbar-width: thin; scrollbar-color: #22c55e #1c1c2a; }
.servico-item {
    background: #252535;
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 14px;
    padding: 14px 16px;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
    display: flex;
    align-items: center;
    gap: 14px;
}
.servico-item:hover, .servico-item.selected {
    border-color: #22c55e;
    background: rgba(34,197,94,0.08);
}
.servico-sigla {
    width: 42px; height: 42px;
    border-radius: 50%;
    background: linear-gradient(135deg, #14532d, #22c55e);
    color: #fff;
    font-weight: 700;
    font-size: 0.9rem;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.servico-info-nome { color: #fff; font-weight: 600; font-size: 0.95rem; }
.servico-info-meta { color: #4ade80; font-size: 0.82rem; margin-top: 2px; }

/* ── BARBEIROS (step 2) ── */
.barbeiros-lista { display: flex; flex-direction: column; gap: 10px; max-height: 340px; overflow-y: auto; scrollbar-width: thin; scrollbar-color: #22c55e #1c1c2a; }
.barbeiro-item {
    background: #252535;
    border: 1px solid rgba(255,255,255,0.07);
    border-radius: 14px;
    padding: 14px 16px;
    cursor: pointer;
    transition: border-color 0.2s, background 0.2s;
    display: flex;
    align-items: center;
    gap: 14px;
}
.barbeiro-item:hover, .barbeiro-item.selected {
    border-color: #22c55e;
    background: rgba(34,197,94,0.08);
}
.barbeiro-avatar {
    width: 42px; height: 42px;
    border-radius: 50%;
    background: linear-gradient(135deg, #14532d, #22c55e);
    color: #fff;
    font-weight: 700;
    font-size: 0.95rem;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.barbeiro-foto {
    width: 42px; height: 42px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
    border: 2px solid #22c55e;
}
.barbeiro-nome-item { color: #fff; font-weight: 600; font-size: 0.95rem; }
.barbeiro-cargo-item { color: #4ade80; font-size: 0.82rem; margin-top: 2px; }

/* ── RESUMO ── */
.resumo-box {
    background: #252535;
    border-radius: 12px;
    padding: 10px 14px;
    margin-bottom: 14px;
    border-left: 3px solid #22c55e;
    font-size: 0.85rem;
    color: #a3e6b8;
    display: flex;
    flex-direction: column;
    gap: 3px;
}

/* ── SECTION HEADER ── */
.section-header {
    background: #2a2a3e;
    color: #e0e0e0;
    text-align: center;
    padding: 9px 16px;
    font-weight: 600;
    font-size: 0.85rem;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    border-radius: 10px 10px 0 0;
}

/* ── DRUM PICKER ── */
.picker-wrapper {
    position: relative;
    background: #21212f;
    border-radius: 0 0 14px 14px;
    height: 180px;
    overflow: hidden;
    margin-bottom: 16px;
}
.picker-highlight {
    position: absolute; top: 50%; transform: translateY(-50%);
    left: 0; right: 0; height: 60px;
    border-top: 1px solid rgba(34,197,94,0.35);
    border-bottom: 1px solid rgba(34,197,94,0.35);
    background: rgba(34,197,94,0.07);
    z-index: 5; pointer-events: none;
}
.picker-fade { position: absolute; left: 0; right: 0; height: 62px; z-index: 6; pointer-events: none; }
.picker-fade.top    { top: 0;    background: linear-gradient(to bottom, #21212f 0%, transparent 100%); }
.picker-fade.bottom { bottom: 0; background: linear-gradient(to top,    #21212f 0%, transparent 100%); }
.picker-columns { display: flex; height: 180px; align-items: stretch; }
.picker-col { flex: 1; overflow: hidden; }
.picker-list {
    height: 180px; overflow-y: scroll;
    scroll-snap-type: y mandatory;
    scrollbar-width: none; -ms-overflow-style: none;
    padding: 60px 0;
}
.picker-list::-webkit-scrollbar { display: none; }
.picker-item {
    height: 60px; display: flex; align-items: center; justify-content: center;
    scroll-snap-align: center;
    color: rgba(255,255,255,0.22); font-size: 1rem; line-height: 1.25;
    text-align: center; cursor: pointer; user-select: none; padding: 0 6px;
    transition: color 0.12s;
}
.picker-item.active { color: #fff; font-size: 1.18rem; font-weight: 700; }
.picker-item.fechado { color: rgba(255,80,80,0.2); }
.picker-separator {
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; font-weight: 300; color: rgba(255,255,255,0.4);
    width: 22px; flex-shrink: 0; z-index: 10; height: 180px;
}
.fechado-msg {
    display: flex; align-items: center; justify-content: center;
    height: 100%; color: rgba(255,100,100,0.55);
    font-size: 0.9rem; text-align: center; padding: 20px;
}

/* ── BOTÕES ── */
.btn-confirmar {
    width: 100%; padding: 15px;
    background: linear-gradient(90deg, #15803d 55%, #22c55e 100%);
    color: #fff; border: none; border-radius: 14px;
    font-size: 1.05rem; font-weight: 700; cursor: pointer;
    transition: background 0.2s, transform 0.15s;
    box-shadow: 0 4px 18px rgba(21,128,61,0.4);
    margin-top: 4px;
}
.btn-confirmar:hover:not(:disabled) {
    background: linear-gradient(90deg, #14532d 55%, #15803d 100%);
    transform: translateY(-2px);
}
.btn-confirmar:disabled {
    background: #2a2a3e; color: rgba(255,255,255,0.25);
    cursor: not-allowed; box-shadow: none;
}
.btn-voltar {
    width: 100%; padding: 10px;
    background: transparent; color: rgba(255,255,255,0.4);
    border: 1px solid rgba(255,255,255,0.1); border-radius: 10px;
    font-size: 0.9rem; cursor: pointer; margin-top: 8px;
    transition: color 0.2s, border-color 0.2s;
}
.btn-voltar:hover { color: #fff; border-color: rgba(255,255,255,0.3); }
.step-label { color: rgba(255,255,255,0.4); font-size: 0.82rem; text-align: center; margin-bottom: 14px; }

@media (max-width: 400px) { .agendamento-card { padding: 20px 14px; } }
</style>

<div class="page-wrapper">
<div class="agendamento-card">

<?php if ($erro_agendamento === 'horario_indisponivel'): ?>
<div style="background:rgba(251,191,36,0.12);border:1px solid rgba(251,191,36,0.3);color:#fbbf24;border-radius:12px;padding:12px 16px;margin-bottom:16px;font-size:0.9rem;text-align:center;">
    ⚠️ Este horário não está mais disponível. Escolha outro.
</div>
<?php elseif ($erro_agendamento): ?>
<div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#f87171;border-radius:12px;padding:12px 16px;margin-bottom:16px;font-size:0.9rem;text-align:center;">
    ❌ Erro ao agendar. Tente novamente.
</div>
<?php endif; ?>
    <h2 class="card-title">✂️ Agendamento</h2>
    <div class="step-indicator">
        <div class="step-dot active" id="dot1"></div>
        <div class="step-dot" id="dot2"></div>
        <div class="step-dot" id="dot3"></div>
    </div>

    <!-- ── STEP 1: SERVIÇO ── -->
    <div class="step active" id="step1">
        <p class="step-label">Passo 1 de 3 — Escolha o serviço</p>
        <div class="servicos-lista">
            <?php if (empty($servicos)): ?>
            <div style="color:rgba(255,255,255,0.4);text-align:center;padding:20px;">Nenhum serviço cadastrado.</div>
            <?php else: foreach ($servicos as $srv):
                $sigla = strtoupper(substr($srv['nome'], 0, 2));
                $preco = 'R$ ' . number_format($srv['preco'], 2, ',', '.');
            ?>
            <div class="servico-item" onclick="selecionarCorte('<?php echo htmlspecialchars(addslashes($srv['nome'])); ?>', this)">
                <div class="servico-sigla"><?php echo $sigla; ?></div>
                <div>
                    <div class="servico-info-nome"><?php echo htmlspecialchars($srv['nome']); ?></div>
                    <div class="servico-info-meta"><?php echo $preco; ?> · <?php echo $srv['duracao']; ?> min</div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>

    <!-- ── STEP 2: BARBEIRO ── -->
    <div class="step" id="step2">
        <p class="step-label">Passo 2 de 3 — Escolha o barbeiro</p>
        <div class="barbeiros-lista">
            <div class="barbeiro-item" onclick="selecionarBarbeiro('', this)">
                <div class="barbeiro-avatar">?</div>
                <div>
                    <div class="barbeiro-nome-item">Sem preferência</div>
                    <div class="barbeiro-cargo-item">Qualquer barbeiro disponível</div>
                </div>
            </div>
            <?php foreach ($lista_barbeiros as $barb):
                $nome_barb = $barb['nome'];
                $foto_barb = $barb['foto'] ?? '';
                $foto_path = '../uploads/perfil/' . $foto_barb;
                $tem_foto  = $foto_barb && file_exists(dirname(__FILE__) . '/../uploads/perfil/' . $foto_barb);
                $iniciais  = strtoupper(implode('', array_map(fn($p)=>$p[0], array_filter(explode(' ', trim($nome_barb))))));
                $iniciais  = substr($iniciais, 0, 2);
            ?>
            <div class="barbeiro-item" onclick="selecionarBarbeiro('<?php echo htmlspecialchars(addslashes($nome_barb)); ?>', this)">
                <?php if ($tem_foto): ?>
                <img src="<?php echo htmlspecialchars($foto_path); ?>" class="barbeiro-foto" alt="<?php echo htmlspecialchars($nome_barb); ?>">
                <?php else: ?>
                <div class="barbeiro-avatar"><?php echo $iniciais; ?></div>
                <?php endif; ?>
                <div>
                    <div class="barbeiro-nome-item"><?php echo htmlspecialchars($nome_barb); ?></div>
                    <div class="barbeiro-cargo-item">✂️ Barbeiro</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button class="btn-voltar" onclick="goToStep(1)">← Voltar</button>
    </div>

    <!-- ── STEP 3: DATA E HORA ── -->
    <div class="step" id="step3">
        <p class="step-label">Passo 3 de 3 — Escolha data e horário</p>

        <div class="resumo-box" id="resumo-box"></div>

        <div class="section-header">Data</div>
        <div class="picker-wrapper">
            <div class="picker-highlight"></div>
            <div class="picker-fade top"></div>
            <div class="picker-fade bottom"></div>
            <div class="picker-columns">
                <div class="picker-col"><div class="picker-list" id="list-dia"></div></div>
                <div class="picker-col" style="flex:1.6;"><div class="picker-list" id="list-mes"></div></div>
                <div class="picker-col"><div class="picker-list" id="list-ano"></div></div>
            </div>
        </div>

        <div class="section-header">Hora</div>
        <div class="picker-wrapper" id="hora-picker">
            <div class="picker-highlight"></div>
            <div class="picker-fade top"></div>
            <div class="picker-fade bottom"></div>
            <div class="picker-columns" id="hora-columns">
                <div class="picker-col"><div class="picker-list" id="list-hora"></div></div>
                <div class="picker-separator">:</div>
                <div class="picker-col"><div class="picker-list" id="list-min"></div></div>
            </div>
        </div>

        <form id="form-agendar" method="post">
            <input type="hidden" name="corte"    id="input-corte">
            <input type="hidden" name="barbeiro" id="input-barbeiro">
            <input type="hidden" name="data"     id="input-data">
            <input type="hidden" name="hora"     id="input-hora">
            <button type="submit" class="btn-confirmar" id="btn-confirmar" disabled>
                Confirmar Agendamento
            </button>
        </form>
        <button class="btn-voltar" onclick="goToStep(2)">← Voltar</button>
    </div>
</div>
</div>

<?php include '../include/footer.php'; ?>

<script>
const dadosBarbeiros = <?php echo json_encode($dados_barbeiros); ?>;
const cortePreSelecionado  = <?php echo json_encode($corte_pre); ?>;
const barbeiroPreSelecionado = <?php echo json_encode($barbeiro_pre); ?>;

const MESES = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
const DIA_ABREV = {'Segunda-feira':'Seg','Terça-feira':'Ter','Quarta-feira':'Qua','Quinta-feira':'Qui','Sexta-feira':'Sex','Sábado':'Sáb','Domingo':'Dom'};
const ITEM_H = 60;

let selectedCorte    = '';
let selectedBarbeiro = '';
let diasData         = dadosBarbeiros[''] || [];

// ── STEPS ─────────────────────────────────────────────────────────────────────
function goToStep(n) {
    document.querySelectorAll('.step').forEach((s,i) => s.classList.toggle('active', i+1 === n));
    document.querySelectorAll('.step-dot').forEach((d,i) => d.classList.toggle('active', i+1 <= n));
}

// ── STEP 1: selecionar serviço ─────────────────────────────────────────────
function selecionarCorte(nome, el) {
    document.querySelectorAll('.servico-item').forEach(e => e.classList.remove('selected'));
    el.classList.add('selected');
    selectedCorte = nome;
    document.getElementById('input-corte').value = nome;
    setTimeout(() => goToStep(2), 180);
}

// ── STEP 2: selecionar barbeiro ────────────────────────────────────────────
function selecionarBarbeiro(nome, el) {
    document.querySelectorAll('.barbeiro-item').forEach(e => e.classList.remove('selected'));
    el.classList.add('selected');
    selectedBarbeiro = nome;
    document.getElementById('input-barbeiro').value = nome;

    diasData = dadosBarbeiros[nome] || dadosBarbeiros[''] || [];

    // Atualiza resumo
    const barb = nome || 'Sem preferência';
    document.getElementById('resumo-box').innerHTML =
        '<span>✂️ <strong>' + selectedCorte + '</strong></span>' +
        '<span>💈 Barbeiro: <strong>' + barb + '</strong></span>';

    buildDatePicker();
    setTimeout(() => goToStep(3), 180);
}

// ── helpers drum ──────────────────────────────────────────────────────────
function scrollTo(list, idx) { list.scrollTop = idx * ITEM_H; }
function activeIdx(list)      { return Math.round(list.scrollTop / ITEM_H); }
function markActive(lists, idx) {
    lists.forEach(list => {
        list.querySelectorAll('.picker-item').forEach((el, i) => el.classList.toggle('active', i === idx));
    });
}

function syncLists(lists, onSettle) {
    let busy = false, timer;
    lists.forEach(list => {
        list.addEventListener('scroll', function () {
            if (busy) return; busy = true;
            const top = this.scrollTop;
            lists.forEach(o => { if (o !== this) o.scrollTop = top; });
            busy = false;
            clearTimeout(timer);
            timer = setTimeout(() => {
                const idx = activeIdx(lists[0]);
                lists.forEach(l => scrollTo(l, idx));
                markActive(lists, idx);
                onSettle(idx);
            }, 130);
        });
    });
}

// ── time picker ────────────────────────────────────────────────────────────
function buildTimePicker(dateIdx) {
    const dia     = diasData[dateIdx];
    const columns = document.getElementById('hora-columns');

    columns.innerHTML = `
        <div class="picker-col"><div class="picker-list" id="list-hora"></div></div>
        <div class="picker-separator">:</div>
        <div class="picker-col"><div class="picker-list" id="list-min"></div></div>`;

    const noSlots = !dia || dia.status === 'fechado' || !dia.horarios_disponiveis || dia.horarios_disponiveis.length === 0;
    if (noSlots) {
        const msg = (dia && dia.status === 'fechado') ? '🚫 Fechado neste dia' : '😔 Sem horários disponíveis';
        columns.innerHTML = `<div class="fechado-msg">${msg}</div>`;
        document.getElementById('input-hora').value = '';
        document.getElementById('btn-confirmar').disabled = true;
        return;
    }

    const listH = document.getElementById('list-hora');
    const listM = document.getElementById('list-min');
    const horarios = dia.horarios_disponiveis;

    horarios.forEach(h => {
        const [hh, mm] = h.split(':');
        listH.innerHTML += `<div class="picker-item" data-hora="${h}">${hh}</div>`;
        listM.innerHTML += `<div class="picker-item" data-hora="${h}">${mm}</div>`;
    });

    scrollTo(listH, 0); scrollTo(listM, 0);
    markActive([listH, listM], 0);
    document.getElementById('input-hora').value = horarios[0];
    document.getElementById('btn-confirmar').disabled = false;

    syncLists([listH, listM], idx => {
        const el = listH.querySelectorAll('.picker-item')[idx];
        if (el) document.getElementById('input-hora').value = el.dataset.hora;
    });
}

// ── date picker ────────────────────────────────────────────────────────────
function buildDatePicker() {
    const listDia = document.getElementById('list-dia');
    const listMes = document.getElementById('list-mes');
    const listAno = document.getElementById('list-ano');
    listDia.innerHTML = ''; listMes.innerHTML = ''; listAno.innerHTML = '';

    diasData.forEach((dia, idx) => {
        const date   = new Date(dia.data + 'T12:00:00');
        const dayNum = String(date.getDate()).padStart(2, '0');
        const month  = MESES[date.getMonth()];
        const year   = date.getFullYear();
        const abrev  = DIA_ABREV[dia.label] || dia.label.slice(0,3);
        const closed = dia.status === 'fechado';
        const cls    = closed ? 'picker-item fechado' : 'picker-item';
        listDia.innerHTML += `<div class="${cls}">${abrev}<br><strong>${dayNum}</strong></div>`;
        listMes.innerHTML += `<div class="${cls}">${month}.</div>`;
        listAno.innerHTML += `<div class="${cls}">${year}</div>`;
    });

    const firstOpen = diasData.findIndex(d => d.status === 'aberto');
    const startIdx  = firstOpen >= 0 ? firstOpen : 0;
    [listDia, listMes, listAno].forEach(l => scrollTo(l, startIdx));
    markActive([listDia, listMes, listAno], startIdx);
    document.getElementById('input-data').value = diasData[startIdx]?.data || '';
    buildTimePicker(startIdx);

    syncLists([listDia, listMes, listAno], idx => {
        const dia = diasData[idx];
        document.getElementById('input-data').value = dia?.data || '';
        buildTimePicker(idx);
    });
}

// ── init ───────────────────────────────────────────────────────────────────
if (cortePreSelecionado) {
    // Veio de cortes.php com serviço já selecionado
    selectedCorte = cortePreSelecionado;
    document.getElementById('input-corte').value = cortePreSelecionado;
    // Marcar o card do serviço visualmente
    document.querySelectorAll('.servico-item').forEach(el => {
        if (el.getAttribute('onclick').includes(cortePreSelecionado)) el.classList.add('selected');
    });
    goToStep(2);
}
</script>
