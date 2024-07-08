<?php

namespace App\Http\Controllers;

use App\Consts\ErrorMessages;
use Illuminate\Http\Request;
use App\Helper\TransactionHelper;

class TransactionController extends Controller
{
    public function update(Request $request)
    {
        abort_if(!$request->query('reference') || !$request->query('status') || !$request->query('external_reference'), 409, ErrorMessages::$TRANSACTION_STATUS_UPDATER_ERROR);

        $data = TransactionHelper::updateDeposit($request->query('reference'), $request->query('external_reference'), $request->query('status'));

        return response()->json($data, 202);
    }
}
