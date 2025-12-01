<?php
header('Content-Type: text/html; charset=utf-8');
// Buscar foto do usuário logado se estiver logado
$foto_usuario = null;
if (isset($_SESSION['LOGADO']) && $_SESSION['LOGADO'] === true) {
    include_once dirname(__FILE__) . '/../config/db.php';
    $nome_usuario = $_SESSION['NOME_USUARIO'];
    $sql_foto = "SELECT foto FROM usuario WHERE nome = ?";
    $stmt_foto = $conn->prepare($sql_foto);
    $stmt_foto->bind_param("s", $nome_usuario);
    $stmt_foto->execute();
    $result_foto = $stmt_foto->get_result();
    if ($user_data = $result_foto->fetch_assoc()) {
        $foto_usuario = $user_data['foto'];
    }
    $stmt_foto->close();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>

    <link rel="stylesheet" href="../assets/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light">
  <div class="container-fluid">
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a href="../home" class="nav-link">Menu</a>
        </li>
      </ul>
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
        <?php if (isset($_SESSION['LOGADO']) && $_SESSION['LOGADO'] === true): ?>
          <?php if (isset($_SESSION['TIPO_USUARIO']) && $_SESSION['TIPO_USUARIO'] === 'admin'): ?>
            <li class="nav-item">
              <a href="../admin/index.php" class="nav-link admin-link">
                <span class="admin-badge">👑 Admin</span>
              </a>
            </li>
          <?php endif; ?>
          <li class="nav-item">
            <a href="../perfil/index.php" class="nav-link welcome-link d-flex align-items-center">
              <?php if (!empty($foto_usuario) && file_exists(dirname(__FILE__) . '/../uploads/perfil/' . $foto_usuario)): ?>
                <img src="../uploads/perfil/<?= htmlspecialchars($foto_usuario) ?>" alt="Foto de Perfil" class="foto-perfil-header me-2">
              <?php else: ?>
                <div class="foto-perfil-header-placeholder me-2">
                  <span class="user-icon-small">👤</span>
                </div>
              <?php endif; ?>
              <span class="user-name">Bem-vindo, <?= htmlspecialchars($_SESSION['NOME_USUARIO'] ?? 'Usuário') ?></span>
            </a>
          </li>
          <li class="nav-item">
            <a href="../logout.php" class="nav-link">Sair</a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a href="../login.php" class="nav-link">Login</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<style>
  .navbar {
    background-color: #222 !important;
  }
  .navbar .navbar-brand,
  .navbar .nav-link {
    color: #fff !important;
  }
  
  .navbar-toggler {
    border-color: rgba(255, 255, 255, 0.5);
  }
  
  .navbar-toggler-icon {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.85%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
  }
  
  .nav-link:hover {
    color: #d54f4f !important;
  }
  
  .welcome-link {
    color: #ffd700 !important; /* Cor dourada para destacar */
    font-weight: 500;
    transition: color 0.3s ease;
  }
  
  .welcome-link:hover {
    color: #ffed4e !important; /* Cor mais clara no hover */
    text-decoration: none;
  }
  
  /* Foto de perfil no header */
  .foto-perfil-header {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #ffd700;
    flex-shrink: 0;
  }
  
  .foto-perfil-header-placeholder {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #555;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #ffd700;
    flex-shrink: 0;
  }
  
  .user-icon-small {
    font-size: 16px;
    color: #ffd700;
  }
  
  /* Botão Admin */
  .admin-link {
    color: #ff6b6b !important;
    background: rgba(255, 107, 107, 0.1);
    border-radius: 15px;
    padding: 5px 10px !important;
    border: 1px solid rgba(255, 107, 107, 0.3);
    transition: all 0.3s ease;
    display: inline-flex !important;
    align-items: center;
  }
  
  .admin-link:hover {
    background: rgba(255, 107, 107, 0.2) !important;
    color: #ff5252 !important;
    transform: scale(1.05);
  }
  
  .admin-badge {
    font-size: 12px;
    font-weight: bold;
  }
  
  /* Responsividade */
  @media (max-width: 991px) {
    .user-name {
      display: inline;
      margin-left: 0;
    }
    
    .welcome-link {
      justify-content: flex-start !important;
    }
    
    .navbar-nav {
      padding: 10px 0;
    }
    
    .nav-item {
      padding: 5px 0;
    }
    
    .admin-link,
    .welcome-link {
      padding: 8px 12px !important;
    }
  }
  
  @media (min-width: 992px) {
    .navbar-nav .nav-item {
      display: flex;
      align-items: center;
    }
    
    .navbar-nav .nav-link {
      display: flex;
      align-items: center;
    }
  }
  
</style>

