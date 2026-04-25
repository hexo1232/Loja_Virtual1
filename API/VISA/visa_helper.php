<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use CyberSource\Api\PaymentsApi;
use CyberSource\Configuration;
use CyberSource\ApiClient;
use CyberSource\Model\CreatePaymentRequest;
use CyberSource\Model\Ptsv2paymentsClientReferenceInformation;
use CyberSource\Model\Ptsv2paymentsProcessingInformation;
use CyberSource\Model\Ptsv2paymentsPaymentInformation;
use CyberSource\Model\Ptsv2paymentsPaymentInformationCard;
use CyberSource\Model\Ptsv2paymentsOrderInformation;
use CyberSource\Model\Ptsv2paymentsOrderInformationAmountDetails;
use CyberSource\Model\Ptsv2paymentsOrderInformationBillTo;

function processarPagamentoVisa($total, $cardNumber, $expirationMonth, $expirationYear, $cvv) {
    $config = new Configuration();
    $config->setHost("https://apitest.cybersource.com");
    $config->setApiKeyID("d8d17a81-d534-44b3-9612-639cb0254ab2");
    $config->setSecretKey("YLGKM5bTiIzfwcz0Ho39Pb6rWTXzet6Jzlcm+jbqxXE=");
    $config->setMerchantID("mtblack_1753140139");

    $client = new ApiClient($config);
    $api = new PaymentsApi($client);

    $request = new CreatePaymentRequest();

    $clientRef = new Ptsv2paymentsClientReferenceInformation([
        "code" => "TEST_COMPRA_" . time()
    ]);
    $request->setClientReferenceInformation($clientRef);

    $processingInfo = new Ptsv2paymentsProcessingInformation([
        "capture" => true
    ]);
    $request->setProcessingInformation($processingInfo);

    $paymentCard = new Ptsv2paymentsPaymentInformationCard([
        "number" => $cardNumber,
        "expirationMonth" => $expirationMonth,
        "expirationYear" => $expirationYear,
        "securityCode" => $cvv
    ]);
    $paymentInfo = new Ptsv2paymentsPaymentInformation([
        "card" => $paymentCard
    ]);
    $request->setPaymentInformation($paymentInfo);

    $amountDetails = new Ptsv2paymentsOrderInformationAmountDetails([
        "totalAmount" => $total,
        "currency" => "USD"
    ]);
    $billTo = new Ptsv2paymentsOrderInformationBillTo([
        "firstName" => "Cliente",
        "lastName" => "Teste",
        "address1" => "Rua Falsa 123",
        "locality" => "Maputo",
        "administrativeArea" => "MZ",
        "postalCode" => "1100",
        "country" => "MZ",
        "email" => "cliente@email.com",
        "phoneNumber" => "840000000"
    ]);
    $orderInfo = new Ptsv2paymentsOrderInformation([
        "amountDetails" => $amountDetails,
        "billTo" => $billTo
    ]);
    $request->setOrderInformation($orderInfo);

    try {
        $response = $api->createPayment($request);
        return $response;
    } catch (Exception $e) {
        return ['erro' => $e->getMessage()];
    }
}
