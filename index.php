<?php
// Inicialização de variáveis
$salarioInput = $_GET['salary'] ?? null;
$dataInput = $_GET['data'] ?? date('Y-m-d');

$resultadoCalculado = false;
$mensagemErro = '';
$salarioFormatado = '';
$salarioMinimoFormatado = '';
$restoFormatado = '';
$quociente = 0;
$dataConsultaFormatada = '';

// Processamento do formulário
if (isset($salarioInput)) {
    $salarioFloat = (float)$salarioInput;
    $dataParaAPI = date('d/m/Y', strtotime($dataInput));
    $dataConsultaFormatada = $dataParaAPI;

    $url = "https://api.bcb.gov.br/dados/serie/bcdata.sgs.1619/dados?formato=json&dataInicial={$dataParaAPI}&dataFinal={$dataParaAPI}";
    $apiResponse = @file_get_contents($url);

    if ($apiResponse === false) {
        $mensagemErro = "Falha ao conectar à API do Banco Central do Brasil. Por favor, tente novamente mais tarde.";
    } else {
        $dados = json_decode($apiResponse, true);

        if (!empty($dados) && isset($dados[0]['valor'])) {
            $valorSalarioMinimo = (float)$dados[0]['valor'];

            if ($valorSalarioMinimo > 0) {
                $quociente = intdiv($salarioFloat, $valorSalarioMinimo);
                $resto = $salarioFloat % $valorSalarioMinimo;
                $resultadoCalculado = true;
            } else {
                $mensagemErro = "O valor do salário mínimo retornado para esta data é zero ou inválido.";
            }
        } else {
            $mensagemErro = "Não foram encontrados dados do salário mínimo para a data informada. Lembre-se que os dados começam em 01/07/1994.";
        }
    }
    
    if ($resultadoCalculado) {
        $padrão = numfmt_create("pt_BR", NumberFormatter::CURRENCY);
        $salarioFormatado = numfmt_format_currency($padrão, $salarioFloat, "BRL");
        $salarioMinimoFormatado = numfmt_format_currency($padrão, $valorSalarioMinimo, "BRL");
        $restoFormatado = numfmt_format_currency($padrão, $resto, "BRL");
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cálculo de Salário Mínimo Histórico</title>
    <link rel="stylesheet" href="style01.css">
</head>
<body>
    
    <main>
        <h1>Salário Mínimo Histórico</h1>
        <p>Descubra quantos salários mínimos um salário equivalia em uma data específica.</p>
        
        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="get">
            <label for="salary">Salário (R$)</label>
            <input type="number" name="salary" id="salary" value="<?= $salarioInput ?>" min="0" step="0.01" required>
            
            <label for="data">Data de Consulta (a partir de 01/07/1994)</label>
            <input type="date" name="data" id="data" min="1994-07-01" max="<?= date('Y-m-d') ?>" value="<?= $dataInput ?>" required>
            
            <input type="submit" value="Calcular Equivalência">
        </form>
    </main>

    <?php if ($mensagemErro): ?>
        <section class="error">
            <h2>Ocorreu um Erro</h2>
            <p><?= $mensagemErro ?></p>
        </section>
    <?php elseif ($resultadoCalculado): ?>
        <section>
            <h2>Resultado da Análise</h2>
            <p>Considerando o salário mínimo de <strong><?= $salarioMinimoFormatado ?></strong> na data de <strong><?= $dataConsultaFormatada ?></strong>:</p>
            <p>Quem recebia <strong><?= $salarioFormatado ?></strong> ganhava o equivalente a <strong><?= $quociente ?></strong> salário(s) mínimo(s) + <strong><?= $restoFormatado ?></strong>.</p>
        </section>
    <?php endif; ?>

</body>
</html>