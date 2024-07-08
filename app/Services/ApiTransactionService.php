<?php

namespace App\Services;

use GuzzleHttp\Client;
use App\Models\Transaction;
use App\Consts\ErrorMessages;
use App\Utils\TransactionUtils;
use Illuminate\Support\Facades\Log;
use App\Enums\TransactionStatusEnum;
use GuzzleHttp\Exception\RequestException;

class ApiTransactionService
{
    /**
     * Make deposit
     *
     * @param float $amount
     * @param string $phone
     * @param string $description
     * @return mixed
     */
    public function deposit(float $amount, string $phone, string $description): mixed
    {
        $client = new Client();

        $depositUrl = env('CAMPAY_URL') . '/collect/';
        $tokenUrl = env('CAMPAY_URL') . '/token/';

        $amount = addslashes($amount);
        $phone = addslashes($phone);
        $description = addslashes($description);
        $reference = TransactionUtils::generateReference();

        $tokenRequestheaders = [
            'Content-Type' => 'application/json'
        ];

        Log::info('Reference : ' . $reference);

        // Deposit request data
        $data = [
            "amount" => $amount,
            "currency" => "XAF",
            "from" => $phone,
            "description" => $description,
            "external_reference" => $reference,
            "external_user" => ""
        ];

        $depositRequestBody = json_encode($data);

        // Token request data
        $tokenData = [
            "username" => env('CAMPAY_USERNAME'),
            "password" => env('CAMPAY_PASSWORD')
        ];

        $tokenRequestBody = json_encode($tokenData);

        try {
            // Obtain token response data
            $tokenRequest = $client->post($tokenUrl, [
                'headers' => $tokenRequestheaders,
                'body' => $tokenRequestBody,
                'verify' => false
            ]);

            $tokenRes = json_decode($tokenRequest->getBody()->getContents(), true);
            $token = $tokenRes['token'];

            Log::info('Token: ' . $tokenRes['token']);

            // Deposit request with with new token
            $depositRequestheaders = [
                'Authorization' => 'Token ' . $token,
                'Content-Type' => 'application/json'
            ];

            $depositRequest = $client->post($depositUrl, [
                'headers' => $depositRequestheaders,
                'body' => $depositRequestBody,
                'verify' => false
            ]);

            $depositRes = json_decode($depositRequest->getBody()->getContents(), true);
            $statusCode = $depositRequest->getStatusCode();

            Log::info('Deposit Response Body: ' . json_encode($depositRes, true));
            Log::info('Deposit Status Code: ' . $statusCode);

            return [
                'external_reference' => $depositRes['reference'],
                'reference' => $reference,
                'is_error' => false,
            ];
        } catch (RequestException $e) {

            // Http exception management
            $response = $e->getResponse();
            $responseBody = $response->getBody()->getContents();

            Log::error('GuzzleHTTP Error Response: ' . $responseBody);

            // decode JSON response
            $errorData = json_decode($responseBody, true);
            if ($errorData) {
                Log::error('Error Message: ' . $errorData['message']);
                Log::error('Error Code: ' . $errorData['error_code']);
            }
            return [
                'message' => $this->getCorrectMessage($errorData['error_code']),
                'is_error' => true,
            ];
        }
    }


    public function withdraw()
    {
        // TODO: Transaction opperation with API.
    }


    /**
     * Get transaction status
     *
     * @param string $reference
     * @param Transaction $transaction
     * @return void
     */
    public function getTransactionStatus(string $reference, Transaction $transaction)
    {
        $client = new Client();

        $transactionStatusUrl = env('CAMPAY_URL') . "/transaction/$reference/";
        $tokenUrl = env('CAMPAY_URL') . '/token/';

        $tokenRequestheaders = [
            'Content-Type' => 'application/json'
        ];

        // Token request data
        $tokenData = [
            "username" => env('CAMPAY_USERNAME'),
            "password" => env('CAMPAY_PASSWORD')
        ];

        $tokenRequestBody = json_encode($tokenData);

        try {
            // Obtain token response data
            $tokenRequest = $client->post($tokenUrl, [
                'headers' => $tokenRequestheaders,
                'body' => $tokenRequestBody,
                'verify' => false
            ]);

            $tokenRes = json_decode($tokenRequest->getBody()->getContents(), true);
            $token = $tokenRes['token'];

            Log::info('Token: ' . $tokenRes['token']);

            // Transaction status request with with new token
            $transactionStatusRequestheaders = [
                'Authorization' => 'Token ' . $token,
                'Content-Type' => 'application/json'
            ];

            $transactionStatusRequest = $client->get($transactionStatusUrl, [
                'headers' => $transactionStatusRequestheaders,
                'verify' => false
            ]);

            $transactionStatusRes = json_decode($transactionStatusRequest->getBody()->getContents(), true);
            $statusCode = $transactionStatusRequest->getStatusCode();

            Log::info('Transaction Status Response Body: ' . json_encode($transactionStatusRes, true));
            Log::info('Transaction  Status Status Code: ' . $statusCode);

            $transaction->status = $this->matchStatus($transactionStatusRes['status']);
            $transaction->save();
        } catch (RequestException $e) {

            // Http exception management
            $response = $e->getResponse();
            $responseBody = $response->getBody()->getContents();

            Log::error('GuzzleHTTP Error Response: ' . $responseBody);
        }
    }


    /**
     * Get Api balance.
     *
     * @return float
     */
    public function getBalance(): float
    {
        $client = new Client();

        $balanceUrl = env('CAMPAY_URL') . "/balance/";
        $tokenUrl = env('CAMPAY_URL') . '/token/';

        $tokenRequestheaders = [
            'Content-Type' => 'application/json'
        ];

        // Token request data
        $tokenData = [
            "username" => env('CAMPAY_USERNAME'),
            "password" => env('CAMPAY_PASSWORD')
        ];

        $tokenRequestBody = json_encode($tokenData);

        try {
            // Obtain token response data
            $tokenRequest = $client->post($tokenUrl, [
                'headers' => $tokenRequestheaders,
                'body' => $tokenRequestBody,
                'verify' => false
            ]);

            $tokenRes = json_decode($tokenRequest->getBody()->getContents(), true);
            $token = $tokenRes['token'];

            Log::info('Token: ' . $tokenRes['token']);

            // Balance request with with new token
            $balanceRequestheaders = [
                'Authorization' => 'Token ' . $token,
                'Content-Type' => 'application/json'
            ];

            $balanceRequest = $client->get($balanceUrl, [
                'headers' => $balanceRequestheaders,
                'verify' => false
            ]);

            $balanceRes = json_decode($balanceRequest->getBody()->getContents(), true);
            $statusCode = $balanceRequest->getStatusCode();

            Log::info('Balance Response Body: ' . json_encode($balanceRes, true));
            Log::info('Balance Status Code: ' . $statusCode);
            Log::info('Total Balance: ' . $balanceRes['total_balance']);

            return $balanceRes['total_balance'];
        } catch (RequestException $e) {

            // Http exception management
            $response = $e->getResponse();
            $responseBody = $response->getBody()->getContents();

            Log::error('GuzzleHTTP Error Response: ' . $responseBody);
        }
    }


    /**
     * Match app transaction status with api transaction status.
     *
     * @param int $status
     * @return string
     */
    public function matchStatus(string $status): string
    {
        switch ($status) {
            case 'SUCCESSFUL':
                return TransactionStatusEnum::COMPLETED->value;
                break;

            case 'FAILED':
                return TransactionStatusEnum::FAILED->value;
                break;

            default:
                return TransactionStatusEnum::PENDING->value;
                break;
        }
    }


    /**
     * Get correct error message.
     *
     * @param string $error_code
     * @return string
     */
    protected function getCorrectMessage(string $error_code): string
    {
        switch ($error_code) {
            case 'ER101':
                return ErrorMessages::$PAYMENT_ER101;
                break;

            case 'ER102':
                return ErrorMessages::$PAYMENT_ER102;
                break;

            case 'ER201':
                return ErrorMessages::$PAYMENT_ER201;
                break;

            case 'ER301':
                return ErrorMessages::$PAYMENT_ER301;
                break;
        }
    }
}
