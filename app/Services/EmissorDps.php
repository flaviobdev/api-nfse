<?php

namespace App\Services;

use Nfse\Dto\Nfse\DpsData;
use Nfse\Dto\Nfse\NfseData;
use Nfse\Enums\TipoAmbiente;
use Nfse\Http\Client\SefinClient;
use Nfse\Http\Contracts\SefinNacionalInterface;
use Nfse\Http\Exceptions\NfseApiException;
use Nfse\Http\NfseContext;
use Nfse\Signer\Certificate;
use Nfse\Signer\XmlSigner;
use Nfse\Xml\DpsXmlBuilder;
use Nfse\Xml\NfseXmlParser;

class EmissorDps
{
    private SefinNacionalInterface $sefinClient;

    public function __construct(
        private NfseContext $context,
        private bool $leiauteNt009EmProducao = false,
        ?SefinNacionalInterface $sefinClient = null,
    ) {
        $this->sefinClient = $sefinClient ?? new SefinClient($context);
    }

    public function montarXml(DpsData $dps): string
    {
        $xml = (new DpsXmlBuilder)->build($dps);

        $leiaute = new DpsLeiaute;

        return $this->usaLeiauteNt009() ? $leiaute->nt009($xml) : $leiaute->vigente($xml);
    }

    public function emitir(DpsData $dps): NfseData
    {
        $certificado = new Certificate($this->context->certificatePath, $this->context->certificatePassword);
        $xmlAssinado = (new XmlSigner($certificado))->sign($this->montarXml($dps), 'infDPS');

        $resposta = $this->sefinClient->emitirNfse(base64_encode(gzencode($xmlAssinado)));

        if (!empty($resposta->erros)) {
            throw NfseApiException::responseError('Erro na emissão: ' . json_encode($resposta->erros));
        }

        if (!$resposta->nfseXmlGZipB64) {
            throw NfseApiException::responseError('Resposta sem XML da NFS-e.');
        }

        return (new NfseXmlParser)->parse(gzdecode(base64_decode($resposta->nfseXmlGZipB64)));
    }

    private function usaLeiauteNt009(): bool
    {
        return $this->context->ambiente === TipoAmbiente::Homologacao || $this->leiauteNt009EmProducao;
    }
}
