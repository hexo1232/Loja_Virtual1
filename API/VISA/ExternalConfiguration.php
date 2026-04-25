<?php
namespace SampleCode;

use CyberSource\Authentication\Core\MerchantConfiguration;
use CyberSource\Logging\LogConfiguration;

class ExternalConfiguration
{
    public static function getMerchantConfig()
    {
        $config = new MerchantConfiguration();
        $config->setAuthenticationType("http_signature");
        $config->setMerchantID("mtblack_1753140139");
        $config->setApiKeyID("c867b070-1d38-44a8-9fba-04b4af1da779");
        $config->setSecretKey("KRlL0zcbewqyCc6U98qxQO/hqFOnyvcUMLvDeqhKbUE=");
        $config->setRunEnvironment("apitest.cybersource.com");

        // Configurar logging com depuração
        $logConfig = new LogConfiguration();
        $logConfig->enableLog = true; // Habilita o logging
        $logDirectory = __DIR__ . '/logs';
        $logConfig->logDirectory = $logDirectory;
        $logConfig->logFilename = 'cybersource.log';

        // Verificar se o diretório de logs existe e é gravável
        if (!is_dir($logDirectory)) {
            mkdir($logDirectory, 0777, true); // Cria o diretório se não existir
        }
        if (!is_writable($logDirectory)) {
            // Adicione uma mensagem de erro visível (remova após testes)
            file_put_contents('php://stderr', "Diretório de logs '$logDirectory' não é gravável.\n");
        }

        $config->setLogConfiguration($logConfig);

        return $config;
    }
}