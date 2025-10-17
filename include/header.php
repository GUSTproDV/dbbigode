<?php

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
        <li class="nav-item">
          <a href="../pessoa" class="nav-link">Pessoas</a>
        </li>
        <li class="nav-item">
          <a href="../usuario" class="nav-link">Usuarios</a>
        </li>
        <li class="nav-item">
          <a href="../produto" class="nav-link">Produto</a>
        </li>
        <li class="nav-item1 d-flex align-items-center">
          <?php if (isset($_SESSION['LOGADO']) && $_SESSION['LOGADO'] === true): ?>
            <span class="nav-link me-2">Bem-vindo, <?= htmlspecialchars($_SESSION['NOME_USUARIO'] ?? 'Usuário') ?></span>
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
  
</style>

