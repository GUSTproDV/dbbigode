<?php 
    session_start(); // Inicia a sessão
    include '../config/db.php';
    include '../include/header.php'; 
?>

<div class="container-home">
    <div class="home-bg">
        <h2>Bem-vindo à INVICTUS</h2>
        <div class="btn-group">
            <a href="cortes.php" class="agendar">Agendar Horário</a>
            <a href="listar.php" class="listar">Meus Horários</a>
        </div>
    </div>
    <a href="https://www.instagram.com/invictusbarbeariaoficiall/" target="_blank">
        <img src="../assets/instagram.png" alt="Instagram" class="instagram-logo">
    </a>
   

<style>
    body {
        background: linear-gradient(120deg, #1a1a1a 60%, #8d6742 90%, #fffbe6 100%);
        min-height: 100vh;
        margin: 0;
        font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
    }
    .container-home {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .home-bg {
        background: rgba(26,26,26,0.98);
        border-radius: 24px;
        box-shadow: 0 8px 32px rgba(141,103,66,0.18), 0 2px 12px rgba(255,255,255,0.08);
        padding: 48px 32px;
        max-width: 400px;
        width: 100%;
        text-align: center;
        position: relative;
        border: 2px solid #8d6742;
    }
    h2 {
        margin-bottom: 30px;
        font-size: 2.2rem;
        color: #fffbe6;
        text-shadow: 2px 2px 8px #8d6742;
        font-weight: 700;
    }
    .btn-group {
        display: flex;
        flex-direction: column;
        gap: 18px;
        margin-top: 32px;
    }
    .agendar, .listar {
        display: block;
        width: 100%;
        border: none;
        padding: 18px 0;
        font-size: 1.2rem;
        color: #fffbe6;
        background: linear-gradient(90deg, #8d6742 60%, #fffbe6 100%);
        border-radius: 10px;
        text-decoration: none;
        font-weight: bold;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: background 0.2s, color 0.2s, box-shadow 0.2s;
        letter-spacing: 1px;
    }
    .agendar:hover, .listar:hover {
        background: linear-gradient(90deg, #fffbe6 60%, #8d6742 100%);
        color: #1a1a1a;
        box-shadow: 0 4px 16px rgba(141,103,66,0.18);
    }
    .instagram-logo {
        max-width: 48px;
        margin: 24px auto 0 auto;
        display: block;
    }
    @media (max-width: 600px) {
        .home-bg {
            padding: 24px 8px;
            max-width: 95vw;
        }
        h2 {
            font-size: 1.3rem;
        }
        .instagram-logo {
            max-width: 32px;
            margin-top: 18px;
        }
    }
</style>

<?php 
    include '../include/footer.php'; 
?>