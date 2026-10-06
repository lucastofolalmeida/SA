<?php

$leitura = 0;
$limiteVelocidade  = 80.00;
$limiteTemperatura = 105.00;
$limiteConsumo     = 75.00;
$limiteVibracao    = 7.00;

function classificarLeitura($leitura, $limiteVelocidade, $limiteTemperatura, $limiteConsumo, $limiteVibracao)
{
    $falhas = [];
    $severidade = [];
    $falhas[] = 'Tudo certo';
    $severidade[] = 'verde';

    if ((float) $leitura['velocidade_kmh'] > $limiteVelocidade) {
        $falhas[] = 'Velocidade acima do limite';
        $severidade[] = 'amarelo';
    } else if ((float) $leitura['velocidade_kmh'] > 2 * $limiteVelocidade) {
        $falhas[] = 'Velocidade muito acima do limite';
        $severidade[] = 'vermelho';
    }

    if ((float) $leitura['temperatura_motor_c'] > $limiteTemperatura) {
        $falhas[] = 'Temperatura acima do limite';
        $severidade[] = 'amarelo';
    } else if ((float) $leitura['temperatura_motor_c'] > 2 * $limiteTemperatura) {
        $falhas[] = 'Temperatura muito acima do limite';
        $severidade[] = 'vermelho';
    }

    if ((float) $leitura['consumo_litros_hora'] > $limiteConsumo) {
        $falhas[] = 'Consumo acima do limite';
        $severidade[] = 'amarelo';
    } else if ((float) $leitura['consumo_litros_hora'] > 2 * $limiteConsumo) {
        $falhas[] = 'Consumo muito acima do limite';
        $severidade[] = 'vermelho';
    }

    if ((float) $leitura['vibracao_mm_s'] > $limiteVibracao) {
        $falhas[] = 'Vibração acima do limite';
        $severidade[] = 'amarelo';
    } else if ((float) $leitura['vibracao_mm_s'] > 2 * $limiteVibracao) {
        $falhas[] = 'Vibração muito acima do limite';
        $severidade[] = 'vermelho';
    }

    return $falhas && $severidade;
}
?>