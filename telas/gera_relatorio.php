<?php
// gera_relatorio.php
// Tela que MONTA o pedido do relatorio. Nao consulta nada:
// apenas envia os parametros por GET para visualiza_relatorio.php.
//
// Por que GET e nao POST: os parametros ficam no endereco, entao o relatorio
// pode ser salvo nos favoritos, recarregado sem reenviar o formulario e
// compartilhado por link. Formulario que grava dados usa POST; formulario
// que apenas consulta usa GET.

$hoje = date('Y-m-d');
$inicio_padrao = date('Y-m-d', strtotime('-30 days'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gerar relatório</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background: #f4f6f8; margin: 0; padding: 24px; color: #222; }
        .cartao { max-width: 560px; margin: 0 auto; background: #fff; padding: 24px 28px; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,.12); }
        h1 { margin-top: 0; font-size: 1.4rem; }
        fieldset { border: 1px solid #d0d7de; border-radius: 6px; margin: 0 0 18px; padding: 12px 16px 16px; }
        legend { font-weight: bold; padding: 0 6px; }
        label { display: block; margin: 10px 0 4px; font-size: .9rem; }
        select, input { width: 100%; padding: 8px; border: 1px solid #b6bec6; border-radius: 4px; box-sizing: border-box; font-size: 1rem; }
        .linha { display: flex; gap: 12px; }
        .linha > div { flex: 1; }
        .ajuda { font-size: .8rem; color: #666; margin-top: 4px; }
        button { background: #0b5cad; color: #fff; border: 0; padding: 10px 20px; border-radius: 4px; font-size: 1rem; cursor: pointer; }
        button:hover { background: #094a8c; }
    </style>
</head>
<body>
<div class="cartao">
    <h1>Gerar relatório</h1>

    <form method="GET" action="visualiza_relatorio.php">

        <!-- Parte 1: tipo de relatorio -->
        <fieldset>
            <legend>Tipo</legend>
            <label for="tipo">Tipo de relatório</label>
            <select name="tipo" id="tipo" required>
                <option value="desempenho">Desempenho</option>
                <option value="consumo">Consumo de energia</option>
                <option value="eficiencia">Eficiência operacional</option>
                <option value="manutencao">Manutenção</option>
            </select>
        </fieldset>

        <!-- Parte 2: periodo -->
        <fieldset>
            <legend>Período</legend>
            <div class="linha">
                <div>
                    <label for="data_inicio">De</label>
                    <input type="date" name="data_inicio" id="data_inicio"
                           value="<?= $inicio_padrao ?>" max="<?= $hoje ?>" required>
                </div>
                <div>
                    <label for="data_fim">Até</label>
                    <input type="date" name="data_fim" id="data_fim"
                           value="<?= $hoje ?>" max="<?= $hoje ?>" required>
                </div>
            </div>
        </fieldset>

        <!-- Parte 3: filtros (todos opcionais; vazio = sem filtro) -->
        <fieldset>
            <legend>Filtros</legend>

            <label for="trem">Trem</label>
            <input type="text" name="trem" id="trem" placeholder="Código ou nome (vazio = todos)">

            <label for="status">Status da viagem <small>(eficiência operacional)</small></label>
            <select name="status" id="status">
                <option value="">Todos</option>
                <option value="concluida">Concluída</option>
                <option value="cancelada">Cancelada</option>
                <option value="atrasada">Atrasada</option>
            </select>

            <label for="severidade">Severidade do alerta <small>(manutenção)</small></label>
            <select name="severidade" id="severidade">
                <option value="">Todas</option>
                <option value="baixa">Baixa</option>
                <option value="media">Média</option>
                <option value="alta">Alta</option>
                <option value="critica">Crítica</option>
            </select>
            <div class="ajuda">Filtros que não se aplicam ao tipo escolhido são ignorados.</div>
        </fieldset>

        <button type="submit">Gerar relatório</button>
    </form>
</div>

<script>
    // Mantem o periodo coerente: "Até" nunca anterior a "De".
    const inicio = document.getElementById('data_inicio');
    const fim = document.getElementById('data_fim');
    function sincroniza() {
        fim.min = inicio.value;
        if (fim.value && fim.value < inicio.value) fim.value = inicio.value;
    }
    inicio.addEventListener('change', sincroniza);
    sincroniza();
</script>
</body>
</html>