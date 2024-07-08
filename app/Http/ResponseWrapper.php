<?php

namespace App\Http;

/**
 *  Wraps a new response
 *  @author <>
 *
 */

class ResponseWrapper
{

    /**
     *  Returns a new response of the request
     *  @param String $status
     *  @param String $message
     *  @param Array $data
     * @return \Illuminate\Http\JsonResponse
     */

    public static function sendResponse($message, $data = [], $statusCode = 200, $status = "success")
    {

        $message = $message == 'F' ? 'Fetched data' : $message;

        $responsePayload = [
            'version' => 'v1',
            'status'  => $status,
            'statusCode' => $statusCode,
            'message' => $message,
            'data'    => $data
        ];

        return response()->json($responsePayload, $statusCode);
    }
}
