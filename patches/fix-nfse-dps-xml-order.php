<?php

// Fix: cLocEmi deve vir antes de tpEmit no infDPS (schema NFSe Nacional).
// A biblioteca nfse-nacional/nfse-php tem a ordem invertida, causando rejeição E1235.

$file = __DIR__ . '/../vendor/nfse-nacional/nfse-php/src/Xml/DpsXmlBuilder.php';

if (!file_exists($file)) {
    exit(0);
}

$content = file_get_contents($file);

$wrong = "appendElement(\$parent, 'tpEmit', \$data->tipoEmitente);\n        \$this->appendElement(\$parent, 'cLocEmi', \$data->codigoLocalEmissao);";
$fixed = "appendElement(\$parent, 'cLocEmi', \$data->codigoLocalEmissao);\n        \$this->appendElement(\$parent, 'tpEmit', \$data->tipoEmitente);";

if (strpos($content, $wrong) !== false) {
    file_put_contents($file, str_replace($wrong, $fixed, $content));
    echo "patches/fix-nfse-dps-xml-order: cLocEmi/tpEmit corrigido.\n";
} else {
    echo "patches/fix-nfse-dps-xml-order: ja aplicado ou nao necessario.\n";
}
