<?php

namespace Tests\Unit;

use App\Services\DpsLeiaute;
use DOMDocument;
use DOMXPath;
use PHPUnit\Framework\TestCase;

class DpsLeiauteTest extends TestCase
{
    private const XML_REJEITADO = '<DPS xmlns="http://www.sped.fazenda.gov.br/nfse" versao="1.01"><infDPS Id="DPS310620023500464900016500001000000000000027"><tpAmb>2</tpAmb><dhEmi>2026-11-26T16:29:30-03:00</dhEmi><verAplic>ConsolidaERP-1.0</verAplic><serie>1</serie><nDPS>27</nDPS><dCompet>2026-11-26</dCompet><tpEmit>1</tpEmit><cLocEmi>3106200</cLocEmi><prest><CNPJ>35004649000165</CNPJ><IM>14133710017</IM><regTrib><opSimpNac>3</opSimpNac><regApTribSN>1</regApTribSN><regEspTrib>0</regEspTrib></regTrib></prest><toma><CNPJ>05545623000163</CNPJ><xNome>EMM INDUSTRIA E COMERCIO DE COSMETICOS LTDA</xNome></toma><serv><locPrest><cLocPrestacao>3106200</cLocPrestacao></locPrest><cServ><cTribNac>010701</cTribNac><xDescServ>TESTE GERAR FINANCEIRO</xDescServ><cTribMun>001</cTribMun></cServ></serv><valores><vServPrest><vServ>150.00</vServ></vServPrest></valores></infDPS></DPS>';

    public function test_nt009_insere_indicador_do_destinatario_logo_apos_o_tomador(): void
    {
        $xml = (new DpsLeiaute)->nt009(self::XML_REJEITADO);

        $this->assertSame(
            ['tpAmb', 'dhEmi', 'verAplic', 'serie', 'nDPS', 'dCompet', 'tpEmit', 'finNFSe', 'cLocEmi', 'prest', 'toma', 'indDest', 'serv', 'valores'],
            $this->filhosDoInfDps($xml)
        );
        $this->assertSame('0', $this->valor($xml, 'indDest'));
    }

    public function test_nt009_insere_indicador_apos_o_prestador_quando_nao_ha_tomador(): void
    {
        $semTomador = preg_replace('#<toma>.*</toma>#', '', self::XML_REJEITADO);

        $filhos = $this->filhosDoInfDps((new DpsLeiaute)->nt009($semTomador));

        $this->assertSame(['prest', 'indDest', 'serv'], array_slice($filhos, array_search('prest', $filhos, true), 3));
    }

    public function test_nt009_nao_duplica_indicador_ja_informado(): void
    {
        $leiaute = new DpsLeiaute;

        $xml = $leiaute->nt009($leiaute->nt009(self::XML_REJEITADO));

        $this->assertSame(1, substr_count($xml, '<indDest>'));
    }

    public function test_leiaute_vigente_nao_recebe_indicador_do_destinatario(): void
    {
        $xml = (new DpsLeiaute)->vigente(self::XML_REJEITADO);

        $this->assertNotContains('indDest', $this->filhosDoInfDps($xml));
        $this->assertNotContains('finNFSe', $this->filhosDoInfDps($xml));
    }

    private function filhosDoInfDps(string $xml): array
    {
        $nomes = [];
        foreach ($this->xpath($xml)->query('/n:DPS/n:infDPS/*') as $filho) {
            $nomes[] = $filho->localName;
        }

        return $nomes;
    }

    private function valor(string $xml, string $campo): string
    {
        return $this->xpath($xml)->query('/n:DPS/n:infDPS/n:' . $campo)->item(0)->textContent;
    }

    private function xpath(string $xml): DOMXPath
    {
        $dom = new DOMDocument;
        $dom->loadXML($xml);
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('n', 'http://www.sped.fazenda.gov.br/nfse');

        return $xpath;
    }
}
