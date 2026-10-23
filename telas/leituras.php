<?php require_once __DIR__ . '/notificacoes_alertas.php'; ?>

<h1>FerroHub - Alertas</h1>

<div class="resumo-alertas">
    <p>Alertas críticos: <strong><?= $totalCriticos ?></strong></p>
    <p>Alertas de atenção: <strong><?= $totalAtencao ?></strong></p>
</div>

<form method="GET" action="leitura.php" class="filtros">
    <label for="severidade">Severidade:</label>
    <select name="severidade" id="severidade">
        <option value="">Todas</option>
        <option value="critica"
            <?= $filtroSeveridade === 'critica' ? 'selected' : '' ?>>
            Crítica
        </option>
        <option value="atencao"
            <?= $filtroSeveridade === 'atencao' ? 'selected' : '' ?>>
            Atenção
        </option>
    </select>

    <label for="tipo">Tipo:</label>
    <select name="tipo" id="tipo">
        <option value="">Todos</option>
        <?php foreach ($regras as $chave => $regra): ?>
            <option value="<?= e($chave) ?>"
                <?= $filtroTipo === $chave ? 'selected' : '' ?>>
                <?= e($regra['rotulo']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Filtrar</button>
    <a href="leitura.php">Limpar</a>
</form>

<div class="lista-alertas">
    <?php if (empty($visiveis)): ?>
        <p>Nenhum alerta encontrado para os filtros selecionados.</p>
    <?php else: ?>
        <?php foreach ($visiveis as $a): ?>
            <article class="alerta <?= e($a['severidade']) ?>">
                <h3>
                    <?= e($a['rotulo']) ?>
                    <?= $a['severidade'] === 'critica'
                        ? '— muito acima do limite'
                        : '— acima do limite' ?>
                </h3>

                <p>
                    <strong>Locomotiva:</strong>
                    <?= e($a['locomotiva']) ?>
                </p>

                <p>
                    <strong>Data e hora:</strong>
                    <?= e($a['data_hora']) ?>
                </p>

                <p>
                    <strong>Valor registrado:</strong>
                    <?= e($a['valor']) ?>
                    <?= e($a['unidade']) ?>
                </p>

                <p>
                    <strong>Limite:</strong>
                    <?= e($a['limite']) ?>
                    <?= e($a['unidade']) ?>
                </p>

                <p>
                    <strong>Severidade:</strong>
                    <?= $a['severidade'] === 'critica'
                        ? 'Crítica'
                        : 'Atenção' ?>
                </p>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>