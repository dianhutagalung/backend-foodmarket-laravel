<?php

namespace App\Http\Controllers\API;

use Midtrans\Config;
use Midtrans\Notification;
use App\Models\Transaction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MidtransController extends Controller
{
    public function callback(Request $request)
    {
        // set konfigurasi midtrans
        $a = Config::$serverKey = config('services.midtrans.serverKey');
        $b = Config::$isProduction = config('services.midtrans.isProduction');
        $c = Config::$isSanitized = config('services.midtrans.isSanitized');
        $d = Config::$is3ds = config('services.midtrans.is3ds');
        var_dump($a);
        var_dump($b);
        var_dump($c);
        var_dump($d);
        die;

        // buat instance midtrans notificaton
        $notif = new Notification();

        // assign ke variable untuk memudahkan coding
        $status = $notif->transaction_status;
        $type = $notif->payment_type;
        $fraud = $notif->fraud_status;
        $order_id = $notif->order_id;

        // cari transaksi berdasarkan ID
        $transaction = Transaction::findOrFail($order_id);

        // Handle nofifikasi status midtrans
        if($status == 'capture')
        {
            if($type == 'credit_card')
            {
                if($fraud  == 'challenge')
                {
                    $transaction->status = 'PENDING';
                }else{
                    $transaction->status = 'SUCCESS';
                }
            }
        }
        else if($status == 'settlement'){
            $transaction->status = 'SUCCESS';
        }
        else if($status == 'pendding'){
            $transaction->status = 'PENDING';
        }
        else if($status == 'deny'){
            $transaction->status = 'CANCELLED';
        }
        else if($status == 'expire'){
            $transaction->status = 'CANCELLED';
        }
        else if($status == 'cancel'){
            $transaction->status = 'CANCELLED';
        }

        // simpan transaksi
        $transaction->save();

    }

    public function success()
    {
        return view('midtrans.success');
    }

    public function unfinish()
    {
        return view('midtrans.unfinish');
    }
    
    public function error()
    {
        return view('midtrans.error');
    }
}
