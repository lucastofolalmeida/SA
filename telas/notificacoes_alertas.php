
<?php

require_once __DIR__ . '/conexao.php';

// Limites utilizados para classificação dos alertas.
$limiteVelocidade = 80.00;
$limiteTemperatura = 105.00;
$limiteVibracao = 7.00;

$regras = [
    'velocidade' => [
        'rotulo' => 'Velocidade',
        'campo' => 'velocidade_atual',
        'limite' => $limiteVelocidade,
        'unidade' => 'km/h'
    ],
    'temperatura' => [
        'rotulo' => 'Temperatura do motor',
        'campo' => 'temperatura_do_motor',
        'limite' => $limiteTemperatura,
        'unidade' => '°C'
    ],
    'vibracao' => [
        'rotulo' => 'Vibração',
        'campo' => 'vibracao_mm',
        'limite' => $limiteVibracao,
        'unidade' => 'mm/s'
    ]
];

// Função de proteção para exibição de valores no HTML.
if (!function_exists('e')) {
    function e($valor)
    {
        return htmlspecialchars(
            (string) $valor,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

// Busca as leituras, incluindo sensores vinculados a rotas.
// LEFT JOIN permite que sensores sem locomotiva diretamente
// associada também sejam incluídos.
$sql = "
    SELECT
        l.id_relatorio,
        l.velocidade_atual,
        l.temperatura_do_motor,
        l.nivel_de_combustivel,
        l.vibracao_mm,
        l.data_hora,

        s.id_sensor,
        s.tipo AS tipo_sensor,
        s.nome AS nome_sensor,
        s.local AS local_sensor,

        t.id_trem,
        t.modelo,
        t.prefixo,

        r.id_rota,
        r.origem,
        r.destino

    FROM leituras AS l

    INNER JOIN sensor AS s
        ON l.id_sensor = s.id_sensor

    LEFT JOIN trem AS t
        ON s.id_trem = t.id_trem

    LEFT JOIN rotas AS r
        ON s.id_rota = r.id_rota

    ORDER BY l.data_hora DESC
";

$consulta = $conexao->query($sql);

if (!$consulta) {
    die('Erro ao consultar as leituras.');
}

$alertas = [];

// Classifica as leituras encontradas.
while ($leitura = $consulta->fetch_assoc()) {

    $tipo = $leitura['tipo_sensor'];

    // Ignora tipos sem regra de alerta configurada.
    if (!isset($regras[$tipo])) {
        continue;
    }

    $regra = $regras[$tipo];
    $valorBruto = $leitura[$regra['campo']];

    // Ignora valores nulos ou inválidos.
    if ($valorBruto === null || !is_numeric($valorBruto)) {
        continue;
    }

    $valor = (float) $valorBruto;
    $limite = $regra['limite'];

    // Só gera alerta quando o limite é ultrapassado.
    if ($valor <= $limite) {
        continue;
    }

    // Define a severidade.
    $severidade = $valor > 2 * $limite
        ? 'critica'
        : 'atencao';

    // Identifica a origem da leitura.
    if (!empty($leitura['prefixo'])) {
        $origemSensor = $leitura['prefixo'];
    } elseif (!empty($leitura['modelo'])) {
        $origemSensor = $leitura['modelo'];
    } elseif (
        !empty($leitura['origem'])
        && !empty($leitura['destino'])
    ) {
        $origemSensor =
            $leitura['origem'] . ' → ' . $leitura['destino'];
    } else {
        $origemSensor = $leitura['nome_sensor'];
    }

    $alertas[] = [
        'id_relatorio' => (int) $leitura['id_relatorio'],
        'id_sensor' => (int) $leitura['id_sensor'],
        'tipo' => $tipo,
        'rotulo' => $regra['rotulo'],
        'locomotiva' => $origemSensor,
        'nome_sensor' => $leitura['nome_sensor'],
        'local_sensor' => $leitura['local_sensor'],
        'id_rota' => $leitura['id_rota'],
        'data_hora' => $leitura['data_hora'],
        'valor' => $valor,
        'limite' => $limite,
        'unidade' => $regra['unidade'],
        'severidade' => $severidade
    ];
}

// Recebe e valida os filtros.
$filtroSeveridade = $_GET['severidade'] ?? '';
$filtroTipo = $_GET['tipo'] ?? '';

if (!in_array(
    $filtroSeveridade,
    ['', 'critica', 'atencao'],
    true
)) {
    $filtroSeveridade = '';
}

if (
    $filtroTipo !== ''
    && !isset($regras[$filtroTipo])
) {
    $filtroTipo = '';
}

// Aplica os filtros sem alterar os totais gerais.
$visiveis = array_values(array_filter(
    $alertas,
    function ($alerta) use (
        $filtroSeveridade,
        $filtroTipo
    ) {
        $correspondeSeveridade =
            $filtroSeveridade === ''
            || $alerta['severidade'] === $filtroSeveridade;

        $correspondeTipo =
            $filtroTipo === ''
            || $alerta['tipo'] === $filtroTipo;

        return $correspondeSeveridade
            && $correspondeTipo;
    }
));

// Calcula os totais gerais de alertas.
$totalCriticos = count(array_filter(
    $alertas,
    fn($alerta) => $alerta['severidade'] === 'critica'
));

$totalAtencao = count(array_filter(
    $alertas,
    fn($alerta) => $alerta['severidade'] === 'atencao'
));

?>