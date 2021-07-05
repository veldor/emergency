<?php /** @noinspection PhpMultipleClassDeclarationsInspection */


namespace app\models\utils;


use app\models\database\Bill;
use app\models\database\FirebaseDeviceBinding;
use app\models\database\Transaction;
use app\priv\Info;
use sngrl\PhpFirebaseCloudMessaging\Client;
use sngrl\PhpFirebaseCloudMessaging\Message;
use sngrl\PhpFirebaseCloudMessaging\Notification;
use sngrl\PhpFirebaseCloudMessaging\Recipient\Device;
use sngrl\PhpFirebaseCloudMessaging\Recipient\Topic;
use Throwable;

class FirebaseHandler
{
    public function sendNewBillNotification(Bill $bill): void
    {
        $clients = FirebaseDeviceBinding::findAll(['cottage_number' => $bill->cottageNumber]);
        if (!empty($clients)) {
            $server_key = Info::FIREBASE_SERVER_KEY;
            $client = new Client();
            $client->setApiKey($server_key);
            $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());
            $message = new Message();
            $message->setPriority('high');
            foreach ($clients as $clientItem) {
                $message->addRecipient(new Device($clientItem->token));
            }
            $message->setNotification(new Notification('Выставлен новый счёт', "Счёт №{$bill->id} на сумму {$bill->totalSumm} руб. Просмотрите подробности в приложении, в разделе \"Счета\""));
            $result = $client->send($message);
            $json = $result->getBody()->getContents();
            if (!empty($json)) {
                try {
                    $encoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                    $results = $encoded['results'];
                    foreach ($results as $key => $resultItem) {
                        if (!empty($resultItem['error']) && $resultItem['error'] === 'NotRegistered') {
                            $target = $clients[$key];
                            if ($target !== null) {
                                $target->delete();
                            }
                        }
                    }
                } catch (Throwable $e) {

                }
            }
        }
    }
    public function sendNewTransactionConfirmedNotification(Transaction $transaction): void
    {
        $clients = FirebaseDeviceBinding::findAll(['cottage_number' => $transaction->cottageNumber]);
        if (!empty($clients)) {
            $server_key = Info::FIREBASE_SERVER_KEY;
            $client = new Client();
            $client->setApiKey($server_key);
            $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());
            $message = new Message();
            $message->setPriority('high');
            foreach ($clients as $clientItem) {
                $message->addRecipient(new Device($clientItem->token));
            }
            $message->setNotification(new Notification('Оплата зарегистрирована', "Зарегистрирована оплата на сумму {$transaction->transactionSumm} руб. по счёту №{$transaction->billId}. Спасибо!"));
            $result = $client->send($message);
            $json = $result->getBody()->getContents();
            if (!empty($json)) {
                try {
                    $encoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                    $results = $encoded['results'];
                    foreach ($results as $key => $resultItem) {
                        if (!empty($resultItem['error']) && $resultItem['error'] === 'NotRegistered') {
                            $target = $clients[$key];
                            if ($target !== null) {
                                $target->delete();
                            }
                        }
                    }
                } catch (Throwable $e) {

                }
            }
        }
    }
    public function sendBroadcast(string $title, string $body): array
    {
        $server_key = Info::FIREBASE_SERVER_KEY;
        $client = new Client();
        $client->setApiKey($server_key);
        $client->injectGuzzleHttpClient(new \GuzzleHttp\Client());
        $message = new Message();
        $message->setPriority('high');
        $message->addRecipient(new Topic('news'));$message
        ->setNotification(new Notification($title, $body));
        $result = $client->send($message);
        return ['status' => 'success'];
    }
}