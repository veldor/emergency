<?php


namespace app\models\utils;


use app\models\database\AccrualsMembership;
use app\models\database\AccrualsPower;
use app\models\database\AccrualsTarget;
use app\models\database\Bill;
use app\models\database\PayedMembership;
use app\models\database\PayedPower;
use app\models\database\PayedTarget;
use app\models\database\Transaction;
use app\priv\Info;
use JsonException;

class InputHandler
{

    public function insertData(): array
    {
        $text = file_get_contents('php://input');
        try {
            $data = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
            if(!empty($data)){
                $key = $data['apiKey'];
                if($key === Info::API_KEY){
                    // добавлю\обновлю данные о начислениях по электроэнергии
                    if(!empty($data['powerAccruals'])){
                        foreach ($data['powerAccruals'] as $accrual) {
                            $newAccrual = AccrualsPower::get($accrual['id']);
                            foreach ($accrual as $key=>$value) {
                                $newAccrual->$key = $value;
                            }
                            $newAccrual->save();
                        }
                    }
                    // добавлю\обновлю данные о начислениях по электроэнергии
                    if(!empty($data['membershipAccruals'])){
                        foreach ($data['membershipAccruals'] as $accrual) {
                            $newAccrual = AccrualsMembership::get($accrual['id']);
                            foreach ($accrual as $key=>$value) {
                                $newAccrual->$key = $value;
                            }
                            $newAccrual->save();
                        }
                    }
                    // добавлю\обновлю данные о начислениях по электроэнергии
                    if(!empty($data['targetAccruals'])){
                        foreach ($data['targetAccruals'] as $accrual) {
                            $newAccrual = AccrualsTarget::get($accrual['id']);
                            foreach ($accrual as $key=>$value) {
                                $newAccrual->$key = $value;
                            }
                            $newAccrual->save();
                        }
                    }
                    // добавлю\обновлю данные о начислениях по электроэнергии
                    if(!empty($data['powerPays'])){
                        foreach ($data['powerPays'] as $pay) {
                            $newPayment = PayedPower::get($pay['id']);
                            foreach ($pay as $key=>$value) {
                                $newPayment->$key = $value;
                            }
                            $newPayment->save();
                        }
                    }
                    // добавлю\обновлю данные о начислениях по электроэнергии
                    if(!empty($data['membershipPays'])){
                        foreach ($data['membershipPays'] as $pay) {
                            $newPayment = PayedMembership::get($pay['id']);
                            foreach ($pay as $key=>$value) {
                                $newPayment->$key = $value;
                            }
                            $newPayment->save();
                        }
                    }
                    // добавлю\обновлю данные о начислениях по электроэнергии
                    if(!empty($data['targetPays'])){
                        foreach ($data['targetPays'] as $pay) {
                            $newPayment = PayedTarget::get($pay['id']);
                            foreach ($pay as $key=>$value) {
                                $newPayment->$key = $value;
                            }
                            $newPayment->save();
                        }
                    }
                    // добавлю\обновлю данные о начислениях по электроэнергии
                    if(!empty($data['bills'])){
                        foreach ($data['bills'] as $bill) {
                            $new = false;
                            $newBill = Bill::get($bill['id']);
                            if(empty($newBill->id)){
                                $new = true;
                            }
                            foreach ($bill as $key=>$value) {
                                $newBill->$key = $value;
                            }
                            $newBill->save();
                            if($new){
                                // оповещу о новом выставленном счёте
                                (new FirebaseHandler())->sendNewBillNotification($newBill);
                            }
                        }
                    }
                    // добавлю\обновлю данные о начислениях по электроэнергии
                    if(!empty($data['transactions'])){
                        foreach ($data['transactions'] as $transaction) {
                            $new = false;
                            $newTransaction = Transaction::get($transaction['id']);
                            if(empty($newTransaction->id)){
                                $new = true;
                            }
                            foreach ($transaction as $key=>$value) {
                                $newTransaction->$key = $value;
                            }
                            $newTransaction->save();
                            if($new){
                                // оповещу о новом выставленном счёте
                                (new FirebaseHandler())->sendNewTransactionConfirmedNotification($newTransaction);
                            }
                        }
                    }
                }
            }
            return ['status' => 'success'];
        } catch (JsonException $e) {
            echo "no json!";
        }
        return ['status' => 'failed'];
    }
}