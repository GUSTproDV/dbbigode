<?php
header('Content-Type: text/html; charset=utf-8');
include_once('../config/db.php');
require_once('../include/admin_middleware.php');
verificarAdmin();

// Apenas funcionários podem acessar (não admin superior)
if (isSuperAdmin()) {
    header('Location: index.php');
    exit;
}

$email   = $_SESSION['usuario_logado'];
$sucesso = '';
$erro    = '';

// Buscar foto atual
$stmt = $conn->prepare("SELECT foto, nome FROM usuario WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

$foto_atual = $usuario['foto'] ?? '';

// Processar upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto'])) {
    $file    = $_FILES['foto'];
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $max_size = 3 * 1024 * 1024; // 3MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $erro = "Erro no upload. Tente novamente.";
    } elseif (!in_array($file['type'], $allowed)) {
        $erro = "Formato inválido. Use JPG, PNG ou WEBP.";
    } elseif ($file['size'] > $max_size) {
        $erro = "Arquivo muito grande. Máximo 3MB.";
    } else {
        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = bin2hex(random_bytes(8)) . '.' . strtolower($ext);
        $destino  = dirname(__FILE__) . '/../uploads/perfil/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $destino)) {
            // Remove foto antiga
            if ($foto_atual && file_exists(dirname(__FILE__) . '/../uploads/perfil/' . $foto_atual)) {
                unlink(dirname(__FILE__) . '/../uploads/perfil/' . $foto_atual);
            }

            $stmt = $conn->prepare("UPDATE usuario SET foto = ? WHERE email = ?");
            $stmt->bind_param("ss", $filename, $email);
            $stmt->execute();
            $stmt->close();

            $foto_atual = $filename;
            $sucesso = "Foto atualizada com sucesso!";
        } else {
            $erro = "Não foi possível salvar a foto.";
        }
    }
}

// Remover foto
if (isset($_POST['remover_foto'])) {
    if ($foto_atual && file_exists(dirname(__FILE__) . '/../uploads/perfil/' . $foto_atual)) {
        unlink(dirname(__FILE__) . '/../uploads/perfil/' . $foto_atual);
    }
    $stmt = $conn->prepare("UPDATE usuario SET foto = NULL WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->close();
    $foto_atual = '';
    $sucesso = "Foto removida.";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Foto - Painel</title>
    <link href="../assets/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .admin-header {
            background: linear-gradient(135deg, #0a0a0a 0%, #052e16 55%, #15803d 100%);
            color: white; padding: 1.5rem 0; margin-bottom: 2rem;
            box-shadow: 0 4px 16px rgba(10,10,10,0.4);
        }
        .card-foto {
            background: #fff; border-radius: 16px; padding: 2rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08); max-width: 480px; margin: 0 auto;
        }
        .foto-preview {
            width: 140px; height: 140px; border-radius: 50%;
            object-fit: cover; border: 4px solid #22c55e;
            box-shadow: 0 4px 16px rgba(34,197,94,0.25);
        }
        .foto-placeholder {
            width: 140px; height: 140px; border-radius: 50%;
            background: linear-gradient(135deg, #14532d, #22c55e);
            display: flex; align-items: center; justify-content: center;
            font-size: 3rem; font-weight: 700; color: #fff;
            border: 4px solid #22c55e;
            box-shadow: 0 4px 16px rgba(34,197,94,0.25);
        }
        .upload-area {
            border: 2px dashed #22c55e; border-radius: 12px;
            padding: 24px; text-align: center; cursor: pointer;
            transition: background 0.2s; margin-top: 1.5rem;
        }
        .upload-area:hover { background: #f0fdf4; }
        .upload-area input[type=file] { display: none; }
        .btn-verde {
            background: linear-gradient(90deg, #15803d, #22c55e);
            color: #fff; border: none; border-radius: 10px;
            padding: 10px 24px; font-weight: 600; width: 100%;
            margin-top: 12px; cursor: pointer; transition: opacity 0.2s;
        }
        .btn-verde:hover { opacity: 0.9; }
        .btn-remover {
            background: transparent; color: #dc2626;
            border: 1px solid #dc2626; border-radius: 10px;
            padding: 8px 20px; font-size: 0.9rem; cursor: pointer;
            margin-top: 10px; transition: background 0.2s;
        }
        .btn-remover:hover { background: #fee2e2; }
        #preview-img { display: none; }
    </style>
</head>
<body>
<div class="admin-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-8">
                <h1><i class="fas fa-camera"></i> Minha Foto de Perfil</h1>
                <p>Sua foto aparecerá para os clientes na página de barbeiros</p>
            </div>
            <div class="col-4 text-end">
                <a href="index.php" class="btn btn-light"><i class="fas fa-arrow-left"></i> Voltar</a>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <?php if ($sucesso): ?>
    <div class="alert alert-success alert-dismissible fade show" style="max-width:480px;margin:0 auto 16px;">
        ✅ <?php echo $sucesso; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($erro): ?>
    <div class="alert alert-danger alert-dismissible fade show" style="max-width:480px;margin:0 auto 16px;">
        ❌ <?php echo $erro; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card-foto">
        <div class="text-center mb-3">
            <?php
            $foto_path = $foto_atual ? '../uploads/perfil/' . $foto_atual : '';
            $iniciais  = strtoupper(substr($usuario['nome'] ?? 'F', 0, 2));
            ?>
            <?php if ($foto_atual && file_exists(dirname(__FILE__) . '/../uploads/perfil/' . $foto_atual)): ?>
                <img src="<?php echo $foto_path; ?>?v=<?php echo time(); ?>" class="foto-preview" id="foto-atual" alt="Foto de perfil">
            <?php else: ?>
                <div class="foto-placeholder mx-auto" id="foto-placeholder"><?php echo $iniciais; ?></div>
            <?php endif; ?>
            <img id="preview-img" class="foto-preview mx-auto d-block">
        </div>

        <p class="text-center text-muted mb-0" style="font-size:0.9rem;">
            <strong><?php echo htmlspecialchars($usuario['nome'] ?? ''); ?></strong> · Barbeiro
        </p>

        <form method="POST" enctype="multipart/form-data" id="form-foto">
            <div class="upload-area" onclick="document.getElementById('input-foto').click()">
                <input type="file" name="foto" id="input-foto" accept="image/jpeg,image/png,image/webp" onchange="previewFoto(this)">
                <div style="font-size:2rem;">📷</div>
                <p style="margin:8px 0 4px;font-weight:600;color:#14532d;">Clique para escolher a foto</p>
                <p style="font-size:0.82rem;color:#888;margin:0;">JPG, PNG ou WEBP · máximo 3MB</p>
            </div>
            <button type="submit" class="btn-verde">Salvar Foto</button>
        </form>

        <?php if ($foto_atual): ?>
        <form method="POST" onsubmit="return confirm('Remover sua foto de perfil?')">
            <input type="hidden" name="remover_foto" value="1">
            <button type="submit" class="btn-remover w-100">🗑️ Remover foto atual</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<script src="https://kit.fontawesome.com/a076d05399.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function previewFoto(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        const preview = document.getElementById('preview-img');
        const atual   = document.getElementById('foto-atual');
        const holder  = document.getElementById('foto-placeholder');
        preview.src = e.target.result;
        preview.style.display = 'block';
        if (atual)  atual.style.display  = 'none';
        if (holder) holder.style.display = 'none';
    };
    reader.readAsDataURL(input.files[0]);
}
</script>
</body>
</html>
