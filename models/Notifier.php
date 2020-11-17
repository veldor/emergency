<?php


namespace app\models;


use yii\base\Model;

class Notifier extends Model
{

    public function sendBroadcast(string $message):void
    {
        Viber::sendBroadcast($message);
        Telegram::sendBroadcast($message);
    }
}