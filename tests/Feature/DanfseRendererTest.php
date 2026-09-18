<?php

namespace Tests\Feature;

use App\Services\DanfseRenderer;
use App\Services\NfseService;
use Exception;
use RuntimeException;
use Tests\TestCase;

class DanfseRendererTest extends TestCase
{
    private function xmlCnpj(): string
    {
        return file_get_contents(__DIR__ . '/../Fixtures/nfse.xml');
    }

    private function xmlPessoaFisica(): string
    {
        return file_get_contents(__DIR__ . '/../Fixtures/nfse-pf.xml');
    }

    public function test_extrai_os_dados_da_nfse_com_prestador_pessoa_juridica(): void
    {
        $dados = (new DanfseRenderer)->dados($this->xmlCnpj());

        $this->assertSame('15054862212345678000199000000000000225121645026250', $dados['chave']);
        $this->assertSame(
            'https://www.nfse.gov.br/ConsultaPublica/?tpc=1&chave=15054862212345678000199000000000000225121645026250',
            $dados['linkPublico']
        );
        $this->assertSame('2', $dados['numero']);
        $this->assertSame('Autorizada', $dados['situacao']);
        $this->assertSame('03/12/2025', $dados['competencia']);
        $this->assertSame('03/12/2025 09:51:58', $dados['emissao']);
        $this->assertSame('Pacajá', $dados['localEmissao']);

        $this->assertSame('PRESTADOR TESTE LTDA', $dados['prestador']['nome']);
        $this->assertSame('12.345.678/0001-99', $dados['prestador']['documento']);
        $this->assertSame('2348', $dados['prestador']['im']);
        $this->assertSame('RUA DE TESTE - CENTRO', $dados['prestador']['endereco']);
        $this->assertSame('68485-000', $dados['prestador']['cep']);
        $this->assertSame('Optante - ME/EPP', $dados['prestador']['simples']);

        $this->assertSame('TOMADOR TESTE PESSOA FISICA', $dados['tomador']['nome']);
        $this->assertSame('111.222.333-44', $dados['tomador']['documento']);

        $this->assertSame('040303', $dados['servico']['codigoNacional']);
        $this->assertSame('ultrassonografia', $dados['servico']['discriminacao']);
        $this->assertSame('Operação tributável', $dados['servico']['tributacao']);
        $this->assertSame('Não retido', $dados['servico']['retencao']);

        $this->assertSame('230,00', $dados['valores']['servico']);
        $this->assertSame('230,00', $dados['valores']['baseCalculo']);
        $this->assertSame('5,00', $dados['valores']['aliquota']);
        $this->assertSame('11,50', $dados['valores']['issqn']);
        $this->assertSame('230,00', $dados['valores']['liquido']);
        $this->assertTrue($dados['homologacao']);
    }

    public function test_extrai_os_dados_da_nfse_com_prestador_pessoa_fisica_e_tomador_com_endereco(): void
    {
        $dados = (new DanfseRenderer)->dados($this->xmlPessoaFisica());

        $this->assertSame('987.654.321-00', $dados['prestador']['documento']);
        $this->assertSame('PRESTADORA TESTE PESSOA FISICA', $dados['prestador']['nome']);
        $this->assertSame('Não optante', $dados['prestador']['simples']);

        $this->assertSame('TOMADOR TESTE PESSOA JURIDICA', $dados['tomador']['nome']);
        $this->assertSame('11.222.333/0001-81', $dados['tomador']['documento']);
        $this->assertSame('AVENIDA DE TESTE, 153 - CENTRO', $dados['tomador']['endereco']);
        $this->assertSame('63540-000', $dados['tomador']['cep']);
        $this->assertSame('VARZEA ALEGRE', $dados['tomador']['municipio']);

        $this->assertSame('Retido pelo tomador', $dados['servico']['retencao']);
        $this->assertSame('1.850,00', $dados['valores']['servico']);
        $this->assertSame('92,50', $dados['valores']['issqn']);
        $this->assertSame('1.757,50', $dados['valores']['liquido']);
        $this->assertFalse($dados['homologacao']);
    }

    public function test_o_html_do_danfse_traz_os_campos_obrigatorios(): void
    {
        $html = (new DanfseRenderer)->html($this->xmlCnpj());

        $this->assertStringContainsString('DANFSe', $html);
        $this->assertStringContainsString('15054862212345678000199000000000000225121645026250', $html);
        $this->assertStringContainsString('www.nfse.gov.br/ConsultaPublica', $html);
        $this->assertStringContainsString('PRESTADOR TESTE LTDA', $html);
        $this->assertStringContainsString('TOMADOR TESTE PESSOA FISICA', $html);
        $this->assertStringContainsString('ultrassonografia', $html);
        $this->assertStringContainsString('230,00', $html);
        $this->assertStringContainsString('type="QR"', $html);
    }

    public function test_renderiza_um_pdf_valido(): void
    {
        $pdf = (new DanfseRenderer)->render($this->xmlCnpj());

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertGreaterThan(5000, strlen($pdf));
    }

    public function test_rejeita_xml_invalido(): void
    {
        $this->expectException(RuntimeException::class);

        (new DanfseRenderer)->dados('isso nao e xml');
    }

    public function test_rejeita_xml_sem_inf_nfse(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('infNFSe');

        (new DanfseRenderer)->dados('<?xml version="1.0"?><outraCoisa><a>1</a></outraCoisa>');
    }

    public function test_download_pdf_gera_o_danfse_a_partir_do_xml_da_nota(): void
    {
        $service = $this->serviceComXml($this->xmlCnpj());

        $pdf = $service->downloadPdf(['ambiente' => 'prod', 'cnpj' => '00', 'cert_base64' => '', 'cert_senha' => ''], '1');

        $this->assertStringStartsWith('%PDF-', $pdf);
    }

    public function test_download_pdf_falha_quando_o_xml_nao_e_encontrado(): void
    {
        $service = $this->serviceComXml('');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('XML da NFS-e');

        $service->downloadPdf(['ambiente' => 'prod', 'cnpj' => '00', 'cert_base64' => '', 'cert_senha' => ''], '1');
    }

    private function serviceComXml(string $xml): NfseService
    {
        return new class($xml) extends NfseService
        {
            public function __construct(private string $xmlFixo) {}

            public function xml(array $empresa, string $chave): string
            {
                return $this->xmlFixo;
            }
        };
    }
}
