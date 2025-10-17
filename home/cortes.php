<?php
    include '../include/header.php';

    // Array associativo dos serviços
    $servicos = [
        [
            'sigla' => 'CD',
            'nome' => 'Corte Degradê',
            'categoria' => 'Barbearia',
            'duracao' => '40 min',
            'preco' => 'R$ 35,00',
            'descricao' => 'Corte moderno com degradê na máquina e acabamento na tesoura.'
        ],
        [
            'sigla' => 'CT',
            'nome' => 'Corte à Tesoura',
            'categoria' => 'Barbearia',
            'duracao' => '45 min',
            'preco' => 'R$ 40,00',
            'descricao' => 'Corte tradicional feito totalmente na tesoura, para quem busca precisão.'
        ],
        [
            'sigla' => 'PZ',
            'nome' => 'Pezinho',
            'categoria' => 'Barbearia',
            'duracao' => '15 min',
            'preco' => 'R$ 15,00',
            'descricao' => 'Acabamento do cabelo e barba, deixando tudo alinhado e limpo.'
        ],
        [
            'sigla' => 'BA',
            'nome' => 'Barba',
            'categoria' => 'Barbearia',
            'duracao' => '30 min',
            'preco' => 'R$ 30,00',
            'descricao' => 'Barba feita na máquina, toalha quente e navalha para um acabamento perfeito.'
        ],
        [
            'sigla' => 'CB',
            'nome' => 'Cabelo + Barba',
            'categoria' => 'Barbearia',
            'duracao' => '1h',
            'preco' => 'R$ 60,00',
            'descricao' => 'Combo de corte de cabelo e barba com todos os cuidados.'
        ]
    ];
?>

<div class="servicos-container">
    <h2>Serviços de Barbearia</h2>
    <div class="servicos-grid">
        <?php foreach ($servicos as $servico): ?>
            <div class="servico-card">
                <div class="servico-sigla"><?php echo $servico['sigla']; ?></div>
                <div class="servico-info">
                    <div class="servico-nome"><strong><?php echo $servico['nome']; ?></strong></div>
                    <div class="servico-categoria">Categoria: <?php echo $servico['categoria']; ?></div>
                    <div class="servico-duracao">Duração: <?php echo $servico['duracao']; ?></div>
                    <div class="servico-preco">Preço: <?php echo $servico['preco']; ?></div>
                    <div class="servico-descricao"><?php echo $servico['descricao']; ?></div>
                </div>
                <button class="btn-reservar">
                    <span class="cal-icon">&#128197;</span>
                    <span>RESERVAR</span>
                </button>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
    .servicos-container {
        max-width: 1100px;
        margin: 40px auto;
        padding: 0 16px 32px 16px;
    }
    .servicos-container h2 {
        font-size: 2.2rem;
        color: #8d6742;
        font-weight: 700;
        margin-bottom: 32px;
        text-align: left;
    }
    .servicos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 32px;
    }
    .servico-card {
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 2px 12px rgba(141,103,66,0.10);
        padding: 28px 22px 22px 22px;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        transition: box-shadow 0.2s, transform 0.2s;
        position: relative;
    }
    .servico-card:hover {
        box-shadow: 0 8px 32px rgba(141,103,66,0.18);
        transform: translateY(-6px) scale(1.03);
    }
    .servico-sigla {
        width: 54px;
        height: 54px;
        background: linear-gradient(135deg, #8d6742 60%, #fffbe6 100%);
        color: #fff;
        font-size: 1.4rem;
        font-weight: bold;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 18px;
        box-shadow: 0 2px 8px rgba(141,103,66,0.10);
        letter-spacing: 2px;
    }
    .servico-info {
        width: 100%;
        text-align: left;
        margin-bottom: 18px;
    }
    .servico-nome {
        font-size: 1.18rem;
        color: #1a1a1a;
        margin-bottom: 6px;
    }
    .servico-categoria,
    .servico-duracao,
    .servico-preco {
        font-size: 0.98rem;
        color: #8d6742;
        margin-bottom: 2px;
    }
    .servico-descricao {
        font-size: 0.97rem;
        color: #333;
        margin-top: 10px;
        margin-bottom: 8px;
    }
    .btn-reservar {
        display: flex;
        align-items: center;
        gap: 8px;
        background: transparent;
        color: #2e7d32;
        border: 2px solid #2e7d32;
        border-radius: 8px;
        padding: 8px 18px;
        font-size: 1.07rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.18s, color 0.18s, border-color 0.18s;
        margin-top: 8px;
        outline: none;
    }
    .btn-reservar:hover {
        background: #eafbe6;
        color: #1b5e20;
        border-color: #1b5e20;
    }
    .cal-icon {
        font-size: 1.2rem;
        color: #2e7d32;
        margin-right: 2px;
        display: inline-block;
    }
    @media (max-width: 700px) {
        .servicos-grid {
            grid-template-columns: 1fr;
            gap: 18px;
        }
        .servicos-container h2 {
            font-size: 1.3rem;
        }
    }
</style>

<script>
    document.querySelectorAll('.btn-reservar').forEach(btn => {
        btn.addEventListener('click', function() {
            const servico = btn.parentElement.querySelector('.servico-nome').innerText;
            window.location.href = "agendar.php?servico=" + encodeURIComponent(servico);
        });
    });
</script>

<?php
    include '../include/footer.php';
?>