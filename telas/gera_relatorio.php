
<form method="GET" action="visualiza_relatorio.php">
<label for="tipo">Tipo de relatorio</label>
<select name="tipo" id="tipo" required>
<option value="desempenho">Desempenho</option>
<option value="consumo">Consumo de energia</option>
<option value="eficiencia">Eficiencia operacional</option>
<option value="manutencao">Manutencao</option>
</select>
<!-- periodo e demais filtros -->
<button type="submit">Gerar relatorio</button>
</form>
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=relatorio.csv');
$saida = fopen('php://output', 'w');
// aqui: uma chamada por linha, incluindo o cabecalho
fclose($saida);
exit;