<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>DANFSe {{ $numero }}</title>
    <style>
        body { font-family: sans-serif; font-size: 8pt; color: #000; }
        table { width: 100%; border-collapse: collapse; }
        .box { border: 0.5pt solid #000; margin-bottom: 4pt; }
        .box td { padding: 2pt 4pt; vertical-align: top; }
        .titulo { background: #e8e8e8; font-weight: bold; font-size: 8pt; padding: 2pt 4pt; border-bottom: 0.5pt solid #000; }
        .rotulo { font-size: 6pt; color: #444; text-transform: uppercase; display: block; }
        .valor { font-size: 8pt; }
        .cabecalho td { vertical-align: middle; padding: 4pt; }
        .cabecalho .nome { font-size: 12pt; font-weight: bold; }
        .chave { font-family: monospace; font-size: 7.5pt; letter-spacing: 0.3pt; }
        .direita { text-align: right; }
        .total { font-size: 10pt; font-weight: bold; }
        .discriminacao { white-space: pre-line; }
        .rodape { font-size: 6.5pt; color: #444; text-align: center; margin-top: 4pt; }
        .aviso { text-align: center; font-weight: bold; color: #b00; padding: 2pt; border: 0.5pt solid #b00; margin-bottom: 4pt; }
    </style>
</head>
<body>

@if ($homologacao)
    <div class="aviso">AMBIENTE DE HOMOLOGAÇÃO - SEM VALOR FISCAL</div>
@endif

<table class="box cabecalho">
    <tr>
        <td width="22%" class="direita">
            @if ($linkPublico)
                <barcode code="{{ $linkPublico }}" type="QR" class="barcode" size="0.9" error="M" disableborder="1" />
            @endif
        </td>
        <td width="78%">
            <div class="nome">DANFSe</div>
            Documento Auxiliar da Nota Fiscal de Serviço eletrônica
            <br>
            <div class="rotulo">Chave de acesso</div>
            <span class="chave">{{ $chave }}</span>
            <br>
            <div class="rotulo">Consulte a autenticidade em</div>
            {{ $linkPublico }}
        </td>
    </tr>
</table>

<table class="box">
    <tr><td colspan="4" class="titulo">DADOS DA NFS-e</td></tr>
    <tr>
        <td width="25%"><div class="rotulo">Número da NFS-e</div><div class="valor">{{ $numero }}</div></td>
        <td width="25%"><div class="rotulo">Competência</div><div class="valor">{{ $competencia }}</div></td>
        <td width="25%"><div class="rotulo">Data/hora da emissão</div><div class="valor">{{ $emissao }}</div></td>
        <td width="25%"><div class="rotulo">Situação</div><div class="valor">{{ $situacao }}</div></td>
    </tr>
    <tr>
        <td><div class="rotulo">DPS nº / série</div><div class="valor">{{ $numeroDps }} / {{ $serie }}</div></td>
        <td><div class="rotulo">Município de emissão</div><div class="valor">{{ $localEmissao }}</div></td>
        <td><div class="rotulo">Local da prestação</div><div class="valor">{{ $localPrestacao }}</div></td>
        <td><div class="rotulo">Município de incidência</div><div class="valor">{{ $localIncidencia }}</div></td>
    </tr>
</table>

<table class="box">
    <tr><td colspan="3" class="titulo">PRESTADOR DO SERVIÇO</td></tr>
    <tr>
        <td width="50%"><div class="rotulo">Nome / razão social</div><div class="valor">{{ $prestador['nome'] }}</div></td>
        <td width="30%"><div class="rotulo">CNPJ / CPF</div><div class="valor">{{ $prestador['documento'] }}</div></td>
        <td width="20%"><div class="rotulo">Inscrição municipal</div><div class="valor">{{ $prestador['im'] }}</div></td>
    </tr>
    <tr>
        <td><div class="rotulo">Endereço</div><div class="valor">{{ $prestador['endereco'] }}</div></td>
        <td><div class="rotulo">Município / UF</div><div class="valor">{{ $prestador['municipio'] }}@if ($prestador['uf']) / {{ $prestador['uf'] }}@endif</div></td>
        <td><div class="rotulo">CEP</div><div class="valor">{{ $prestador['cep'] }}</div></td>
    </tr>
    <tr>
        <td><div class="rotulo">E-mail</div><div class="valor">{{ $prestador['email'] }}</div></td>
        <td><div class="rotulo">Telefone</div><div class="valor">{{ $prestador['fone'] }}</div></td>
        <td><div class="rotulo">Simples Nacional / regime</div><div class="valor">{{ $prestador['simples'] }}@if ($prestador['regime'] && $prestador['regime'] !== 'Nenhum') - {{ $prestador['regime'] }}@endif</div></td>
    </tr>
</table>

<table class="box">
    <tr><td colspan="3" class="titulo">TOMADOR DO SERVIÇO</td></tr>
    <tr>
        <td width="50%"><div class="rotulo">Nome / razão social</div><div class="valor">{{ $tomador['nome'] }}</div></td>
        <td width="30%"><div class="rotulo">CNPJ / CPF</div><div class="valor">{{ $tomador['documento'] }}</div></td>
        <td width="20%"><div class="rotulo">Inscrição municipal</div><div class="valor">{{ $tomador['im'] }}</div></td>
    </tr>
    <tr>
        <td><div class="rotulo">Endereço</div><div class="valor">{{ $tomador['endereco'] }}</div></td>
        <td><div class="rotulo">Município / UF</div><div class="valor">{{ $tomador['municipio'] }}@if ($tomador['uf']) / {{ $tomador['uf'] }}@endif</div></td>
        <td><div class="rotulo">CEP</div><div class="valor">{{ $tomador['cep'] }}</div></td>
    </tr>
    <tr>
        <td><div class="rotulo">E-mail</div><div class="valor">{{ $tomador['email'] }}</div></td>
        <td colspan="2"><div class="rotulo">Telefone</div><div class="valor">{{ $tomador['fone'] }}</div></td>
    </tr>
</table>

<table class="box">
    <tr><td colspan="3" class="titulo">SERVIÇO PRESTADO</td></tr>
    <tr>
        <td width="20%"><div class="rotulo">Código de tributação nacional</div><div class="valor">{{ $servico['codigoNacional'] }}</div></td>
        <td colspan="2"><div class="rotulo">Descrição</div><div class="valor">{{ $servico['descricaoNacional'] }}</div></td>
    </tr>
    @if ($servico['codigoMunicipal'] || $servico['descricaoMunicipal'])
        <tr>
            <td><div class="rotulo">Código de tributação municipal</div><div class="valor">{{ $servico['codigoMunicipal'] }}</div></td>
            <td colspan="2"><div class="rotulo">Descrição</div><div class="valor">{{ $servico['descricaoMunicipal'] }}</div></td>
        </tr>
    @endif
    @if ($servico['nbs'] || $servico['descricaoNbs'])
        <tr>
            <td><div class="rotulo">NBS</div><div class="valor">{{ $servico['nbs'] }}</div></td>
            <td colspan="2"><div class="rotulo">Descrição NBS</div><div class="valor">{{ $servico['descricaoNbs'] }}</div></td>
        </tr>
    @endif
    <tr>
        <td colspan="3"><div class="rotulo">Discriminação do serviço</div><div class="valor discriminacao">{{ $servico['discriminacao'] }}</div></td>
    </tr>
    @if ($servico['complemento'])
        <tr>
            <td colspan="3"><div class="rotulo">Informações complementares</div><div class="valor discriminacao">{{ $servico['complemento'] }}</div></td>
        </tr>
    @endif
    <tr>
        <td colspan="2"><div class="rotulo">Tributação do ISSQN</div><div class="valor">{{ $servico['tributacao'] }}</div></td>
        <td><div class="rotulo">Retenção do ISSQN</div><div class="valor">{{ $servico['retencao'] }}</div></td>
    </tr>
</table>

<table class="box">
    <tr><td colspan="4" class="titulo">VALORES</td></tr>
    <tr>
        <td width="25%"><div class="rotulo">Valor do serviço</div><div class="valor">R$ {{ $valores['servico'] }}</div></td>
        <td width="25%"><div class="rotulo">Desconto incondicionado</div><div class="valor">R$ {{ $valores['descontoIncondicionado'] ?: '0,00' }}</div></td>
        <td width="25%"><div class="rotulo">Deduções / reduções</div><div class="valor">R$ {{ $valores['deducoes'] ?: '0,00' }}</div></td>
        <td width="25%"><div class="rotulo">Base de cálculo</div><div class="valor">R$ {{ $valores['baseCalculo'] }}</div></td>
    </tr>
    <tr>
        <td><div class="rotulo">Alíquota (%)</div><div class="valor">{{ $valores['aliquota'] }}</div></td>
        <td><div class="rotulo">ISSQN</div><div class="valor">R$ {{ $valores['issqn'] }}</div></td>
        <td><div class="rotulo">Desconto condicionado</div><div class="valor">R$ {{ $valores['descontoCondicionado'] ?: '0,00' }}</div></td>
        <td><div class="rotulo">Benefício municipal</div><div class="valor">R$ {{ $valores['beneficioMunicipal'] ?: '0,00' }}</div></td>
    </tr>
</table>

<table class="box">
    <tr><td colspan="5" class="titulo">RETENÇÕES FEDERAIS</td></tr>
    <tr>
        <td width="20%"><div class="rotulo">PIS</div><div class="valor">R$ {{ $valores['pis'] ?: '0,00' }}</div></td>
        <td width="20%"><div class="rotulo">COFINS</div><div class="valor">R$ {{ $valores['cofins'] ?: '0,00' }}</div></td>
        <td width="20%"><div class="rotulo">INSS (CP)</div><div class="valor">R$ {{ $valores['inss'] ?: '0,00' }}</div></td>
        <td width="20%"><div class="rotulo">IRRF</div><div class="valor">R$ {{ $valores['irrf'] ?: '0,00' }}</div></td>
        <td width="20%"><div class="rotulo">CSLL</div><div class="valor">R$ {{ $valores['csll'] ?: '0,00' }}</div></td>
    </tr>
</table>

<table class="box">
    <tr>
        <td width="50%"><div class="rotulo">Total de retenções</div><div class="valor">R$ {{ $valores['totalRetido'] ?: '0,00' }}</div></td>
        <td width="50%" class="direita"><div class="rotulo">Valor líquido da NFS-e</div><div class="total">R$ {{ $valores['liquido'] }}</div></td>
    </tr>
</table>

@if ($valores['tributosFederais'] || $valores['tributosEstaduais'] || $valores['tributosMunicipais'] || $valores['percentualSimples'])
    <table class="box">
        <tr><td colspan="4" class="titulo">TRIBUTOS TOTAIS INCIDENTES (LEI 12.741/2012)</td></tr>
        <tr>
            <td width="25%"><div class="rotulo">Federais</div><div class="valor">R$ {{ $valores['tributosFederais'] ?: '0,00' }}</div></td>
            <td width="25%"><div class="rotulo">Estaduais</div><div class="valor">R$ {{ $valores['tributosEstaduais'] ?: '0,00' }}</div></td>
            <td width="25%"><div class="rotulo">Municipais</div><div class="valor">R$ {{ $valores['tributosMunicipais'] ?: '0,00' }}</div></td>
            <td width="25%"><div class="rotulo">% Simples Nacional</div><div class="valor">{{ $valores['percentualSimples'] ?: '-' }}</div></td>
        </tr>
    </table>
@endif

<div class="rodape">
    Documento auxiliar gerado pelo sistema emissor a partir do XML autorizado da NFS-e, conforme NT 008/2026 (SEFIN Nacional).
    NFS-e nº {{ $numero }} / DF-e nº {{ $numeroDfe }} - processada em {{ $processamento }}. Gerado em {{ $geradoEm }}.
</div>

</body>
</html>
