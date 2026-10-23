
<?php

require_once __DIR__ . '/conexao.php';

$limiteVelocidade = 80.00;
$limiteTemperatura = 105.00;
$limiteVibracao = 7.00;

// Regras compatíveis com os sensores existentes no banco.
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

// Busca as leituras, seus sensores e as locomotivas.
$sql = "
    SELECT
        l.id_relatorio,
        l.velocidade_atual,
        l.temperatura_do_motor,
        l.nivel_de_combustivel,
        l.vibracao_mm,
        l.data_hora,
        s.tipo AS tipo_sensor,
        s.nome AS nome_sensor,
        t.id_trem,
        t.modelo,
        t.prefixo
    FROM leituras AS l
    INNER JOIN sensor AS s
        ON l.id_sensor = s.id_sensor
    INNER JOIN trem AS t
        ON s.id_trem = t.id_trem
    ORDER BY l.data_hora DESC
";

$consulta = $conexao->query($sql);

if (!$consulta) {
    die('Erro ao consultar as leituras: ' . $conexao->error);
}

$alertas = [];

// Classifica as leituras encontradas.
while ($leitura = $consulta->fetch_assoc()) {

    $tipo = $leitura['tipo_sensor'];

    // Ignora tipos sem regra de classificação definida.
    if (!isset($regras[$tipo])) {
        continue;
    }

    $regra = $regras[$tipo];
    $valorBruto = $leitura[$regra['campo']];

    // Não classifica valores nulos como se fossem zero.
    if ($valorBruto === null || !is_numeric($valorBruto)) {
        continue;
    }

    $valor = (float) $valorBruto;
    $limite = $regra['limite'];

    // Leituras dentro do limite não geram alertas.
    if ($valor <= $limite) {
        continue;
    }

    $severidade = $valor > 2 * $limite
        ? 'critica'
        : 'atencao';

    $alertas[] = [
        'id_relatorio' => $leitura['id_relatorio'],
        'tipo' => $tipo,
        'rotulo' => $regra['rotulo'],
        'locomotiva' => $leitura['prefixo']
            ?: $leitura['modelo'],
        'data_hora' => $leitura['data_hora'],
        'valor' => $valor,
        'limite' => $limite,
        'unidade' => $regra['unidade'],
        'severidade' => $severidade
    ];
}

// Recebe e valida os filtros da página.
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

// Aplica os filtros sem alterar o total geral.
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

// Calcula os totais gerais.
$totalCriticos = count(array_filter(
    $alertas,
    fn($a) => $a['severidade'] === 'critica'
));

$totalAtencao = count(array_filter(
    $alertas,
    fn($a) => $a['severidade'] === 'atencao'
));

?>
