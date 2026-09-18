<?php

declare(strict_types=1);

namespace App\Services;

use Mpdf\Mpdf;
use RuntimeException;
use SimpleXMLElement;

/**
 * Gera o DANFSe (PDF) a partir do XML autorizado da NFS-e Nacional.
 *
 * A API de geração do DANFSe do Ambiente Nacional foi suspensa pela NT-008/2026
 * e passou a responder 503: o documento auxiliar é agora responsabilidade do
 * sistema emissor. Por isso o PDF é montado aqui, a partir do XML da nota.
 */
class DanfseRenderer
{
    private const CONSULTA_PUBLICA = 'https://www.nfse.gov.br/ConsultaPublica/?tpc=1&chave=';

    private const SIMPLES_NACIONAL = [
        '1' => 'Não optante',
        '2' => 'Optante - MEI',
        '3' => 'Optante - ME/EPP',
    ];

    private const REGIME_ESPECIAL = [
        '0' => 'Nenhum',
        '1' => 'Ato Cooperado',
        '2' => 'Estimativa',
        '3' => 'Microempresa Municipal',
        '4' => 'Notário ou Registrador',
        '5' => 'Profissional Autônomo',
        '6' => 'Sociedade de Profissionais',
    ];

    private const TRIBUTACAO_ISSQN = [
        '1' => 'Operação tributável',
        '2' => 'Exportação de serviço',
        '3' => 'Não incidência',
        '4' => 'Imunidade',
    ];

    private const RETENCAO_ISSQN = [
        '1' => 'Não retido',
        '2' => 'Retido pelo tomador',
        '3' => 'Retido pelo intermediário',
    ];

    public function render(string $xml): string
    {
        $mpdf = new Mpdf([
            'format' => 'A4',
            'margin_top' => 8,
            'margin_bottom' => 8,
            'margin_left' => 8,
            'margin_right' => 8,
            'tempDir' => $this->tempDir(),
        ]);

        $mpdf->SetTitle('DANFSe');
        $mpdf->WriteHTML($this->html($xml));

        return (string) $mpdf->Output('', 'S');
    }

    public function html(string $xml): string
    {
        return view('danfse', $this->dados($xml))->render();
    }

    /**
     * @return array<string, mixed>
     */
    public function dados(string $xml): array
    {
        $inf = $this->infNfse($xml);
        $dps = $inf->DPS->infDPS ?? null;
        $emit = $inf->emit ?? null;
        $prest = $dps->prest ?? null;
        $toma = $dps->toma ?? null;
        $serv = $dps->serv ?? null;
        $valNfse = $inf->valores ?? null;
        $valDps = $dps->valores ?? null;
        $trib = $valDps->trib ?? null;

        $chave = preg_replace('/\D+/', '', (string) ($inf['Id'] ?? ''));
        $localEmissao = $this->t($inf, 'xLocEmi');

        // O XML só traz o código IBGE nos endereços; os nomes vêm nos campos de
        // localização da própria nota, então reaproveitamos o que dá para casar.
        $municipios = array_filter([
            $this->t($dps, 'cLocEmi') => $localEmissao,
            $this->t($inf, 'cLocIncid') => $this->t($inf, 'xLocIncid'),
            $this->t($serv, 'locPrest/cLocPrestacao') => $this->t($inf, 'xLocPrestacao'),
        ]);

        return [
            'chave' => $chave,
            'linkPublico' => $chave === '' ? '' : self::CONSULTA_PUBLICA . $chave,
            'numero' => $this->t($inf, 'nNFSe'),
            'numeroDfe' => $this->t($inf, 'nDFSe'),
            'situacao' => $this->t($inf, 'cStat') === '100' ? 'Autorizada' : $this->t($inf, 'cStat'),
            'emissao' => $this->dataHora($this->t($dps, 'dhEmi') ?: $this->t($inf, 'dhProc')),
            'processamento' => $this->dataHora($this->t($inf, 'dhProc')),
            'competencia' => $this->data($this->t($dps, 'dCompet')),
            'serie' => $this->t($dps, 'serie'),
            'numeroDps' => $this->t($dps, 'nDPS'),
            // Só o tpAmb da DPS diz produção x homologação. O ambGer da NFS-e é
            // o ambiente GERADOR (1-Prefeitura, 2-Sistema Nacional) e não tem
            // relação com isso.
            'homologacao' => $this->t($dps, 'tpAmb') === '2',
            'localEmissao' => $localEmissao,
            'localPrestacao' => $this->t($inf, 'xLocPrestacao'),
            'localIncidencia' => $this->t($inf, 'xLocIncid'),

            'prestador' => [
                'documento' => $this->documento($emit),
                'nome' => $this->t($emit, 'xNome'),
                'fantasia' => $this->t($emit, 'xFant'),
                'im' => $this->t($emit, 'IM') ?: $this->t($prest, 'IM'),
                'endereco' => $this->endereco($emit->enderNac ?? null),
                'municipio' => $municipios[$this->t($emit, 'enderNac/cMun')] ?? ($localEmissao ?: $this->t($emit, 'enderNac/cMun')),
                'uf' => $this->t($emit, 'enderNac/UF'),
                'cep' => $this->cep($this->t($emit, 'enderNac/CEP')),
                'fone' => $this->t($emit, 'fone') ?: $this->t($prest, 'fone'),
                'email' => $this->t($emit, 'email') ?: $this->t($prest, 'email'),
                'simples' => self::SIMPLES_NACIONAL[$this->t($prest, 'regTrib/opSimpNac')] ?? '',
                'regime' => self::REGIME_ESPECIAL[$this->t($prest, 'regTrib/regEspTrib')] ?? '',
            ],

            'tomador' => [
                'documento' => $this->documento($toma),
                'nome' => $this->t($toma, 'xNome'),
                'im' => $this->t($toma, 'IM'),
                'endereco' => $this->endereco($toma->end ?? null),
                'municipio' => $municipios[$this->t($toma, 'end/endNac/cMun')]
                    ?? ($this->t($toma, 'end/endNac/cMun') ?: $this->t($toma, 'end/endExt/cPais')),
                'uf' => $this->t($toma, 'end/endExt/xEstProvReg'),
                'cep' => $this->cep($this->t($toma, 'end/endNac/CEP')),
                'fone' => $this->t($toma, 'fone'),
                'email' => $this->t($toma, 'email'),
            ],

            'servico' => [
                'codigoNacional' => $this->t($serv, 'cServ/cTribNac'),
                'descricaoNacional' => $this->t($inf, 'xTribNac'),
                'codigoMunicipal' => $this->t($serv, 'cServ/cTribMun'),
                'descricaoMunicipal' => $this->t($inf, 'xTribMun'),
                'nbs' => $this->t($serv, 'cServ/cNBS'),
                'descricaoNbs' => $this->t($inf, 'xNBS'),
                'discriminacao' => $this->t($serv, 'cServ/xDescServ'),
                'complemento' => $this->t($serv, 'infoCompl/xInfComp'),
                'tributacao' => self::TRIBUTACAO_ISSQN[$this->t($trib, 'tribMun/tribISSQN')] ?? '',
                'retencao' => self::RETENCAO_ISSQN[$this->t($trib, 'tribMun/tpRetISSQN')] ?? '',
            ],

            'valores' => [
                'servico' => $this->dinheiro($this->t($valDps, 'vServPrest/vServ')),
                'descontoIncondicionado' => $this->dinheiro($this->t($valDps, 'vDescCondIncond/vDescIncond')),
                'descontoCondicionado' => $this->dinheiro($this->t($valDps, 'vDescCondIncond/vDescCond')),
                'deducoes' => $this->dinheiro($this->t($valNfse, 'vCalcDR')),
                'beneficioMunicipal' => $this->dinheiro($this->t($valNfse, 'vCalcBM')),
                'baseCalculo' => $this->dinheiro($this->t($valNfse, 'vBC')),
                'aliquota' => $this->dinheiro($this->t($valNfse, 'pAliqAplic')),
                'issqn' => $this->dinheiro($this->t($valNfse, 'vISSQN')),
                'pis' => $this->dinheiro($this->t($trib, 'tribFed/piscofins/vPis')),
                'cofins' => $this->dinheiro($this->t($trib, 'tribFed/piscofins/vCofins')),
                'inss' => $this->dinheiro($this->t($trib, 'tribFed/vRetCP')),
                'irrf' => $this->dinheiro($this->t($trib, 'tribFed/vRetIRRF')),
                'csll' => $this->dinheiro($this->t($trib, 'tribFed/vRetCSLL')),
                'totalRetido' => $this->dinheiro($this->t($valNfse, 'vTotalRet')),
                'liquido' => $this->dinheiro($this->t($valNfse, 'vLiq')),
                'tributosFederais' => $this->dinheiro($this->t($trib, 'totTrib/vTotTrib/vTotTribFed')),
                'tributosEstaduais' => $this->dinheiro($this->t($trib, 'totTrib/vTotTrib/vTotTribEst')),
                'tributosMunicipais' => $this->dinheiro($this->t($trib, 'totTrib/vTotTrib/vTotTribMun')),
                'percentualSimples' => $this->dinheiro($this->t($trib, 'totTrib/pTotTribSN')),
            ],

            'geradoEm' => date('d/m/Y H:i'),
        ];
    }

    private function infNfse(string $xml): SimpleXMLElement
    {
        // O ADN devolve o XML no namespace default do SPED; removê-lo deixa o
        // documento navegável direto ($inf->emit->CNPJ) sem quebrar prefixos.
        $semNamespace = preg_replace('/\sxmlns="[^"]*"/', '', $xml) ?? $xml;

        $sx = @simplexml_load_string($semNamespace);

        if ($sx === false) {
            throw new RuntimeException('XML da NFS-e inválido: não foi possível interpretar o documento.');
        }

        $inf = $sx->getName() === 'infNFSe' ? $sx : ($sx->infNFSe ?? null);

        if ($inf === null || $inf->getName() !== 'infNFSe') {
            throw new RuntimeException('XML da NFS-e sem o grupo infNFSe.');
        }

        return $inf;
    }

    private function t(?SimpleXMLElement $node, string $path): string
    {
        foreach (explode('/', $path) as $parte) {
            if ($node === null || ! isset($node->{$parte})) {
                return '';
            }

            $node = $node->{$parte};
        }

        return trim((string) $node);
    }

    private function documento(?SimpleXMLElement $node): string
    {
        foreach (['CNPJ', 'CPF', 'NIF'] as $tipo) {
            $valor = $this->t($node, $tipo);

            if ($valor !== '') {
                return $tipo === 'NIF' ? $valor : $this->cpfCnpj($valor);
            }
        }

        return '';
    }

    private function cpfCnpj(string $valor): string
    {
        $digitos = preg_replace('/\D+/', '', $valor) ?? '';

        if (strlen($digitos) === 14) {
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $digitos) ?? $digitos;
        }

        if (strlen($digitos) === 11) {
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digitos) ?? $digitos;
        }

        return $digitos;
    }

    private function cep(string $valor): string
    {
        $digitos = preg_replace('/\D+/', '', $valor) ?? '';

        return strlen($digitos) === 8
            ? substr($digitos, 0, 5) . '-' . substr($digitos, 5)
            : $digitos;
    }

    private function endereco(?SimpleXMLElement $node): string
    {
        $logradouro = $this->t($node, 'xLgr');
        $numero = $this->t($node, 'nro');
        $complemento = $this->t($node, 'xCpl');
        $bairro = $this->t($node, 'xBairro');

        $linha = $logradouro;

        // "0" e "-" são os preenchimentos usados no XML quando não há número.
        if ($numero !== '' && $numero !== '-' && (int) $numero !== 0) {
            $linha .= ', ' . $numero;
        }

        foreach ([$complemento, $bairro] as $extra) {
            if ($extra !== '' && $extra !== '-') {
                $linha .= ' - ' . $extra;
            }
        }

        return trim($linha, ' -');
    }

    private function dinheiro(string $valor): string
    {
        return $valor === '' ? '' : number_format((float) $valor, 2, ',', '.');
    }

    private function data(string $valor): string
    {
        return $this->formatar($valor, 'd/m/Y');
    }

    private function dataHora(string $valor): string
    {
        return $this->formatar($valor, 'd/m/Y H:i:s');
    }

    private function formatar(string $valor, string $formato): string
    {
        if ($valor === '') {
            return '';
        }

        $data = date_create($valor);

        return $data === false ? $valor : $data->format($formato);
    }

    private function tempDir(): string
    {
        $dir = storage_path('app/mpdf');

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        return $dir;
    }
}
