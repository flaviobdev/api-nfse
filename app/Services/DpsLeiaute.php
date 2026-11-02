<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;

class DpsLeiaute
{
    private const NAMESPACE_NFSE = 'http://www.sped.fazenda.gov.br/nfse';
    private const FINALIDADE_REGULAR = '0';
    private const ORDEM_APOS_TPEMIT_VIGENTE = ['cMotivoEmisTI', 'chNFSeRej', 'cLocEmi'];
    private const ORDEM_APOS_TPEMIT_NT009 = ['cMotivoEmisTI', 'finNFSe', 'chNFSeRej', 'cLocEmi'];

    public function vigente(string $xml): string
    {
        [$dom, $xpath, $infDps] = $this->carregar($xml);

        if ($infDps !== null) {
            $this->ordenarAposTpEmit($xpath, $infDps, self::ORDEM_APOS_TPEMIT_VIGENTE);
        }

        return $this->salvar($dom);
    }

    public function nt009(string $xml): string
    {
        [$dom, $xpath, $infDps] = $this->carregar($xml);

        if ($infDps !== null) {
            if ($this->filho($xpath, $infDps, 'finNFSe') === null) {
                $infDps->appendChild($dom->createElementNS(self::NAMESPACE_NFSE, 'finNFSe', self::FINALIDADE_REGULAR));
            }
            $this->ordenarAposTpEmit($xpath, $infDps, self::ORDEM_APOS_TPEMIT_NT009);
        }

        foreach ($xpath->query('/n:DPS/n:infDPS/n:serv/n:cServ') as $cServ) {
            $descricao = $this->filho($xpath, $cServ, 'xDescServ');
            if ($descricao !== null) {
                $cServ->appendChild($descricao);
            }
        }

        return $this->salvar($dom);
    }

    private function carregar(string $xml): array
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadXML($xml);

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('n', self::NAMESPACE_NFSE);

        $infDps = $xpath->query('/n:DPS/n:infDPS')->item(0);

        return [$dom, $xpath, $infDps instanceof DOMElement ? $infDps : null];
    }

    private function salvar(DOMDocument $dom): string
    {
        return str_replace(["\n", "\r", "\t"], '', $dom->saveXML($dom->documentElement));
    }

    private function ordenarAposTpEmit(DOMXPath $xpath, DOMElement $infDps, array $ordem): void
    {
        $anterior = $this->filho($xpath, $infDps, 'tpEmit');
        if ($anterior === null) {
            return;
        }

        foreach ($ordem as $nome) {
            $elemento = $this->filho($xpath, $infDps, $nome);
            if ($elemento === null) {
                continue;
            }
            if ($anterior->nextSibling !== $elemento) {
                $infDps->insertBefore($elemento, $anterior->nextSibling);
            }
            $anterior = $elemento;
        }
    }

    private function filho(DOMXPath $xpath, DOMElement $pai, string $nome): ?DOMElement
    {
        $elemento = $xpath->query('n:' . $nome, $pai)->item(0);

        return $elemento instanceof DOMElement ? $elemento : null;
    }
}
