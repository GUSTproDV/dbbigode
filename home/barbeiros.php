<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
include '../config/db.php';
include '../include/header.php';

$result = $conn->query("SELECT nome, email, foto FROM usuario WHERE tipo_usuario = 'funcionario' AND ativo = 1 ORDER BY nome ASC");
$barbeiros = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $barbeiros[] = $row;
    }
}
?>

<style>
    body {
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 50%, #bbf7d0 100%);
        min-height: 100vh;
    }
    .barbeiros-container {
        max-width: 1100px;
        margin: 40px auto;
        padding: 0 20px 40px;
    }
    .barbeiros-container h2 {
        font-size: 2.2rem;
        color: #14532d;
        font-weight: 700;
        margin-bottom: 8px;
        text-align: center;
    }
    .barbeiros-container p.subtitulo {
        text-align: center;
        color: #4b7c5c;
        margin-bottom: 40px;
        font-size: 1.05rem;
    }
    .barbeiros-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 28px;
    }
    .barbeiro-card {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 4px 18px rgba(22, 163, 74, 0.10);
        padding: 32px 24px 24px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        transition: transform 0.2s, box-shadow 0.2s;
        border: 1px solid #d1fae5;
    }
    .barbeiro-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 10px 32px rgba(22, 163, 74, 0.18);
    }
    .barbeiro-avatar {
        width: 88px;
        height: 88px;
        border-radius: 50%;
        background: linear-gradient(135deg, #14532d 0%, #22c55e 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.4rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 18px;
        box-shadow: 0 4px 14px rgba(22, 163, 74, 0.25);
        letter-spacing: 1px;
        flex-shrink: 0;
    }
    .barbeiro-foto {
        width: 88px;
        height: 88px;
        border-radius: 50%;
        object-fit: cover;
        margin-bottom: 18px;
        box-shadow: 0 4px 14px rgba(22, 163, 74, 0.25);
        border: 3px solid #22c55e;
        flex-shrink: 0;
    }
    .barbeiro-nome {
        font-size: 1.2rem;
        font-weight: 700;
        color: #14532d;
        margin-bottom: 6px;
    }
    .barbeiro-cargo {
        font-size: 0.9rem;
        color: #4b7c5c;
        margin-bottom: 20px;
    }
    .btn-agendar-barbeiro {
        display: inline-block;
        background: linear-gradient(90deg, #15803d 60%, #22c55e 100%);
        color: #fff;
        padding: 10px 24px;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.97rem;
        transition: background 0.2s, transform 0.15s;
        border: none;
        cursor: pointer;
        width: 100%;
    }
    .btn-agendar-barbeiro:hover {
        background: linear-gradient(90deg, #14532d 60%, #15803d 100%);
        color: #fff;
        transform: scale(1.03);
    }
    .sem-barbeiros {
        text-align: center;
        padding: 60px 20px;
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.07);
    }
    .sem-barbeiros .icon { font-size: 3.5rem; margin-bottom: 16px; }
    .sem-barbeiros h4 { color: #4b7c5c; }
    .sem-barbeiros p { color: #888; }
</style>

<div class="barbeiros-container">
    <h2>&#9986;&#65039; Nossos Barbeiros</h2>
    <p class="subtitulo">Escolha o barbeiro e agende seu horário</p>

    <?php if (empty($barbeiros)): ?>
    <div class="sem-barbeiros">
        <div class="icon">&#128088;</div>
        <h4>Nenhum barbeiro disponível no momento</h4>
        <p>Em breve nossa equipe estará disponível para atendimento.</p>
        <a href="agendar.php" class="btn-agendar-barbeiro" style="max-width:220px;display:inline-block;">
            Agendar mesmo assim
        </a>
    </div>
    <?php else: ?>
    <div class="barbeiros-grid">
        <?php foreach ($barbeiros as $barbeiro):
            $iniciais   = strtoupper(implode('', array_map(fn($p) => $p[0], explode(' ', trim($barbeiro['nome'])))));
            $iniciais   = substr($iniciais, 0, 2);
            $foto_file  = $barbeiro['foto'] ?? '';
            $foto_path  = '../uploads/perfil/' . $foto_file;
            $tem_foto   = $foto_file && file_exists(dirname(__FILE__) . '/../uploads/perfil/' . $foto_file);
        ?>
        <div class="barbeiro-card">
            <?php if ($tem_foto): ?>
                <img src="<?php echo htmlspecialchars($foto_path); ?>" class="barbeiro-foto" alt="<?php echo htmlspecialchars($barbeiro['nome']); ?>">
            <?php else: ?>
                <div class="barbeiro-avatar"><?php echo htmlspecialchars($iniciais); ?></div>
            <?php endif; ?>
            <div class="barbeiro-nome"><?php echo htmlspecialchars($barbeiro['nome']); ?></div>
            <div class="barbeiro-cargo">✂️ Barbeiro</div>
            <a href="agendar.php?barbeiro=<?php echo urlencode($barbeiro['nome']); ?>"
               class="btn-agendar-barbeiro">
                Agendar com este barbeiro
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include '../include/footer.php'; ?>
