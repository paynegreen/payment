<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use DB;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Resources\Email;

class PaymentController extends Controller
{
    private $baseUrl;
    private $clientKey;
    private $secretKey;
    private $clienId;

    public function __construct()
    {
        $this->baseUrl = 'https://xchange.korbaweb.com/api/v1.0/collect/';
        // $this->baseUrl = 'https://xchange.korbaweb.com/api/v1.0/disbursement_network_options/';
        $this->clientKey = 'f18c73f589efd86ba35f68aeb9adf9b6be131ce5';
        $this->secretKey = '701fe4de4e776e4c172d259d3db98c2f20a3657c068d494f00d8fc5a989be12d';
        $this->clientId = 105;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->status === 'SUCCESS') {
            $record = DB::table('signups_participant')->where('ref', $request->transaction_id)->update(['status' => 0]);

            if ($record) {
                $participant = DB::table('signups_participant')->where('ref', $request->transaction_id)->first();
                $package = DB::table('core_packages')->where('identifier', $participant->package)->first();

                return [
                    'date' => date('d M Y'),
                    'title' => $participant->title,
                    'name' => $participant->lname,
                    'package' => "{$package->name} USD{$package->price}",
                    'code' => $participant->code
                ];

                $mail = new Email();

                $res = $mail->send($participant->email, 'confirm', [
                    'date' => date('d M Y'),
                    'title' => $participant->title,
                    'name' => $participant->lname,
                    'package' => "{$package->name} USD{$package->price}",
                    'code' => $participant->code
                ]);

                return response()-json([$res]);
            }
        }
        return response()->json(['message' => 'payment failed'], 422);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validedData = $request->validate([
            // 'amount' => 'required|numeric',
            'transaction_id' => 'string|required',
            // 'description' => 'required',
            // 'payer_name' => 'required',
        ]);

        $record = DB::table('signups_participant')->where('id', $request->transaction_id)->first();
        $package = DB::table('core_packages')->where('identifier', $record->package)->first();


        $validedData['transaction_id'] = $record->ref;
        $validedData['amount'] = (float) $package->price;
        $validedData['description'] = 'Payment for ticket to attend event';
        $validedData['payer_name'] = 'Africa Digital Rights Hub';
        $validedData['network_code'] = 'CRD';
        $validedData['callback_url'] = 'https://api.dataprotectionafrica.org/callback';
        $validedData['client_id'] = $this->clientId;

        ksort($validedData);

        $paramsJoined = array();

        foreach ($validedData as $param => $value) {
            $paramsJoined[] = "$param=$value";
        }

        $query = implode('&', $paramsJoined);

        // // Generate HMAC Signature
        $hmac = hash_hmac('sha256', $query, $this->secretKey);

        $client = new Client();

        $result = $client->post($this->baseUrl, [
            'headers' => [
                'Authorization' => "HMAC {$this->clientKey}:{$hmac}"
            ],
            'form_params' => $validedData
        ]);

        return response()->json([
            'data' => json_decode($result->getBody())
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
