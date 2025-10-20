<?php
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>

    <link rel="stylesheet" href="../assets/bootstrap.min.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light">
  <div class="container-fluid">
    <a class="navbar-brand" href="#"></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a href="../home" class="nav-link">Menu </a>
        </li>
        <!-- <li class="nav-item">
          <a href="../pessoa" class="nav-link">Pessoas</a>
        </li> -->
        <li class="nav-item">
          <a href="../usuario" class="nav-link">Usuarios</a>
        </li>
        <li class="nav-item1 d-flex align-items-center">
          <?php if (isset($_SESSION['LOGADO']) && $_SESSION['LOGADO'] === true): ?>
            <a href="../perfil/index.php" class="nav-link me-2 welcome-link d-flex align-items-center">
              <?php if (!empty($foto_usuario) && file_exists(dirname(__FILE__) . '/../uploads/perfil/' . $foto_usuario)): ?>
                <img src="../uploads/perfil/<?= htmlspecialchars($foto_usuario) ?>" alt="Foto de Perfil" class="foto-perfil-header me-2">
              <?php else: ?>
                <div class="foto-perfil-header-placeholder me-2">
                  <span class="user-icon-small">👤</span>
                </div>
              <?php endif; ?>
              Bem-vindo, <?= htmlspecialchars($_SESSION['NOME_USUARIO'] ?? 'Usuário') ?>
            </a>
            <a href="../logout.php" class="nav-link">Sair</a>
          <?php else: ?>
            <a href="../index.php" class="nav-link">Login</a>
          <?php endif; ?>
        </li>
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
 .nav-link:hover {
    color: #d54f4fff !important;
  }
  .nav-item1 {
    position: absolute;
    right: 10px;
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
  }
  
  .user-icon-small {
    font-size: 16px;
    color: #ffd700;
  }
  
</style>

