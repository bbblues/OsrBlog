<?php
$renderList = function (array $items): void {
    if ($items === []) {
        echo '<p class="text-muted small mb-0">Sem dados.</p>';
        return;
    }
    echo '<ul class="list-unstyled mb-0">';
    foreach ($items as $label => $count) {
        echo '<li class="d-flex justify-content-between border-bottom py-1">';
        echo '<span class="text-break me-2">' . htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') . '</span>';
        echo '<span class="badge text-bg-secondary align-self-center">' . (int) $count . '</span>';
        echo '</li>';
    }
    echo '</ul>';
};
?>

<h1 class="h2 mb-4">Estatísticas</h1>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="display-6"><?= (int) $pageviews ?></div>
                <div class="text-muted">Pageviews</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <div class="display-6"><?= (int) $uniqueVisitors ?></div>
                <div class="text-muted">Visitantes únicos</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h2 class="h6 mb-3">Pageviews por dia</h2>
        <canvas id="pageviewsChart" height="100"></canvas>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6">Top 10 páginas</h2>
                <?php $renderList($topPages); ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6">Top 10 referrers</h2>
                <?php $renderList($topReferrers); ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6">Dispositivos</h2>
                <?php $renderList($devices); ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6">Top 10 erros 404</h2>
                <?php $renderList($top404); ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h2 class="h6">Top 10 redirecionamentos (301)</h2>
                <?php $renderList($top301); ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
(function () {
    var labels = <?= $chartLabels ?>;
    var data = <?= $chartData ?>;
    var ctx = document.getElementById('pageviewsChart');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Pageviews',
                data: data,
                borderColor: '#0d6efd',
                tension: 0.3,
                fill: false
            }]
        },
        options: {
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } }
            }
        }
    });
})();
</script>
