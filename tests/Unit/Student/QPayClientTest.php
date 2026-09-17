<?php

use App\Services\Payment\QPayClient;

/**
 * The secure hash is the whole of QPay's security. These are the worked examples
 * from Appendix D of the QCB QPay EZ-Connect Integration Guide v1.8, verbatim.
 */
const QPAY_GUIDE_SECRET = '3aJYQWELpNywSw3O';

it('signs a payment request exactly as the guide', function (): void {
    expect(QPayClient::hash(QPAY_GUIDE_SECRET, [
        'Action' => '0', 'Amount' => '20000', 'BankID' => 'QIB', 'CurrencyCode' => '634',
        'ExtraFields_f14' => 'https://merchantUrl.com/response', 'Lang' => 'en', 'MerchantID' => 'simulator123',
        'MerchantModuleSessionID' => 'f859a6e54bd54de4ad3d', 'NationalID' => '2215275104', 'PUN' => 'f859a6e54bd54de4ad3d',
        'PaymentDescription' => 'SamplePayment', 'Quantity' => '1', 'TransactionRequestDate' => '06082023161317',
    ]))->toBe('2d4cd46694e97d22e3e86d6ec054afbda99e8c1f7ade8d99f4ae07544afa2ab8');
});

it('signs an inquiry and a refund request exactly as the guide', function (): void {
    expect(QPayClient::hash(QPAY_GUIDE_SECRET, [
        'Action' => '14', 'BankID' => 'QIB', 'Lang' => 'en', 'MerchantID' => 'simulator123', 'OriginalPUN' => 'f859a6e54bd54de4ad3d',
    ]))->toBe('ca49b75c35d42f09fafda9f29d560039e4ae6074fcdcf4db854f39ef107afce1');

    expect(QPayClient::hash(QPAY_GUIDE_SECRET, [
        'Action' => '6', 'Amount_1' => '1000', 'BankID' => 'QIB', 'CurrencyCode' => '634', 'Lang' => 'en', 'MerchantID' => 'simulator123',
        'OriginalTransactionPaymentUniqueNumber_1' => 'f859a6e54bd54de4ad3d', 'PUN_1' => '62ab9838993a43a48909', 'RequestDate' => '',
        'TransactionRequestDate' => '06082023161932',
    ]))->toBe('99d5e09f096eeba95e893e7461554b49ba3d9c9c835a28dcc9db5b045cf38cae');
});

it('verifies the guide\'s payment, inquiry and refund responses', function (string $body, string $expected): void {
    $parsed = QPayClient::parseResponse($body);

    expect(QPayClient::responseHashes(QPAY_GUIDE_SECRET, $parsed['values']))->toContain($expected);
})->with([
    'payment' => [
        'Response.AcquirerID=030003&Response.Amount=20000&Response.BankID=QIB&Response.CardExpiryDate=3006&Response.CardHolderName=NOT_CAPTURED&Response.CardNumber=421537******3243&Response.ConfirmationID=202308060113174499433083352&Response.CurrencyCode=634&Response.EZConnectResponseDate=06082023161626&Response.Lang=en&Response.MerchantID=simulator123&Response.MerchantModuleSessionID=f859a6e54bd54de4ad3d&Response.PUN=f859a6e54bd54de4ad3d&Response.Status=0000&Response.StatusMessage=Payment+Processed+Successfully',
        '660432ad41745cd716f8b799196e18c2cf84736f80014cc9816e0d53fd718760',
    ],
    'inquiry' => [
        'Response.Status=0000&Response.StatusMessage=Payment+processed+successfully.&Response.OriginalStatus=0000&Response.OriginalStatusMessage=Payment+processed+successfully.&Response.OriginalConfirmationID=202308060113174499433083352&Response.OriginalPUN=f859a6e54bd54de4ad3d&Response.OriginalExtractStatus=&Response.OriginalReversalStatus=&Response.TransactionResponseDate=06082023161317&Response.Amount=20000&Response.CurrencyCode=634&Response.MerchantID=simulator123&Response.EZConnectResponseDate=07082023144016&Response.BankID=QIB&Response.Quantity=1&Response.ApprovalCode=121212&Response.CardExpiryDate=&Response.CardHolderName=&Response.CardNumber=421537******3243&Response.PaymentDescription=SamplePayment&Response.ItemID=&Response.NationalID=2215275104',
        '373c5b679b27eb0bb6f371faa4b60a15223ee7a2c3a95f593a192d8a2d1e4b76',
    ],
    'refund' => [
        'Response.Amount_1=1000&Response.ConfirmationID_1=&Response.CurrencyCode_1=634&Response.EZConnectRequestStatus=&Response.EZConnectResponseDate=06082023162003&Response.Lang_1=en&Response.OriginalTransactionPaymentUniqueNumber_1=f859a6e54bd54de4ad3d&Response.PUN_1=62ab9838993a43a48909&Response.StatusMessage_1=Refund+Transaction+is+pending&Response.Status_1=5002&Response.TransactionRequestDate_1=06%2F08%2F2023+16%3A19%3A32',
        '91183a574d0a0771704c6ac91befe6890b75ab6c044edd2670ab32e3781a586b',
    ],
]);

it('keeps the dots in Response. field names', function (): void {
    $parsed = QPayClient::parseResponse('Response.Status=0000&Response.SecureHash=ABC');

    expect($parsed['values'])->toBe(['Status' => '0000'])->and($parsed['hash'])->toBe('ABC');
});

it('formats amounts without a decimal point', function (): void {
    expect(QPayClient::minorUnits(10.5))->toBe('1050')
        ->and(QPayClient::minorUnits('200'))->toBe('20000')
        ->and(QPayClient::minorUnits(0.1 + 0.2))->toBe('30')
        ->and(QPayClient::fromMinorUnits('1050'))->toBe(10.5);
});
