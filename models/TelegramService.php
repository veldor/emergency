<?php /** @noinspection PhpUndefinedMethodInspection */


namespace app\models;


use app\models\database\Blacklist_cottages;
use app\models\database\Blacklist_telegram;
use app\models\database\Cottages;
use app\models\database\DefenceDevice;
use app\models\database\DefenceStatusChangeRequest;
use app\models\database\Telegram_clients;
use app\models\database\Telegram_service_clients;
use app\models\utils\GrammarHandler;
use app\models\utils\RawDataHandler;
use app\priv\Info;
use Exception;
use TelegramBot\Api\Client;
use TelegramBot\Api\InvalidJsonException;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class TelegramService
{
    private static Client $bot;
    private static Message $message;

    public static function handleRequest(): void
    {
        try {
            $token = Info::TG_SERVICE_BOT_TOKEN;
            self::$bot = new Client($token);
// команда для start
            self::$bot->command(/**
             * @param $message Message
             */ 'start', static function ($message) {
                self::$message = $message;
                $answer = 'Добро пожаловать! /help для вывода команд';
                /** @var Message $message */
                self::sendMessage($answer);
            });

// команда для помощи
            self::$bot->command('help', static function ($message) {
                try {
                    self::$message = $message;
                    /** @var Message $message */
                    // проверю, зарегистрирован ли пользователь
                    if (Telegram_service_clients::isRegistered(self::$message->getChat()->getId())) {
                        $answer = 'Команды:
/help - вывод справки
/artifacts - проверить наличие аномалий счётчиков';
                    } else {
                        $answer = 'Команды:
/help - вывод справки';
                    }
                    /** @var Message $message */
                    self::sendMessage($answer);
                } catch (Exception $e) {
                    self::sendMessage($e->getMessage());
                }
            });
// команда проверки аномалий счётчиков
            self::$bot->command('artifacts', static function ($message) {
                self::$message = $message;
                if (Telegram_service_clients::isRegistered(self::$message->getChat()->getId())) {
                    // проверю, кто из участков долго не выходил на связь
                    $answer = Cottages::checkLost();
                    self::sendMessage($answer);
                }
            });


            self::$bot->on(/**
             * @param $Update Update
             */ static function ($Update) {
                /** @var Update $Update */
                /** @var Message $message */
                try {
                    self::$message = $Update->getMessage();
                    $msg_text = self::$message->getText();
                    // получен простой текст, обработаю его в зависимости от содержимого
                    $answer = self::handleSimpleText($msg_text);
                    self::sendMessage($answer);
                } catch (Exception $e) {
                    self::sendMessage($e->getMessage());
                }
            }, static function () {
                return true;
            });

            try {
                self::$bot->run();
            } catch (InvalidJsonException $e) {
                // что-то сделаю потом
            }
        } catch (Exception $e) {

        }
    }

    private static function handleSimpleText(string $msg_text): string
    {
        $isRegistered = Telegram_service_clients::isRegistered(self::$message->getChat()->getId());
        if ($msg_text === Info::TG_SERVICE_BOT_SECRET) {
            // зарегистрирую данного пользователя
            if(!$isRegistered){
                (new Telegram_service_clients(['client_id' =>self::$message->getChat()->getId() ]))->save();
                return "Окей, теперь по команде /help доступен расширенный список команд";
            }
            return "Можно больше не вводить, ты уже зарегистрирован. По команде /help доступен расширенный список команд";
        }
        elseif($isRegistered && (GrammarHandler::startsWith($msg_text, "current") || GrammarHandler::startsWith($msg_text, "/current"))){
            $valuesArr = explode(" ", $msg_text);
            if(!empty($valuesArr) && count($valuesArr) === 2){
                $cottageNumber =  $valuesArr[1];
            }
            else{
                $cottageNumber = substr($msg_text, 9);
            }
                if(!empty($cottageNumber)){
                    /** @var Cottages $cottageInfo */
                    $cottageInfo = Cottages::findOne(['cottage_number' => $cottageNumber]);
                    if($cottageInfo !== null){
                        $dataInfo = new RawDataHandler($cottageInfo->last_raw_data);
                        $valueName = 'pin_' . $cottageInfo->channel . '_value';
                        $counted = $dataInfo->$valueName;
                        $startValue = $cottageInfo->initial_value;
                        $total = round($counted + $startValue , 3);
                        return "
                        Начальное значение: $startValue Квт*ч\nСчитыватель насчитал: {$counted} Квт*ч\nИтого: {$total} Квт*ч\nЗаряд батареи: {$dataInfo->batteryLevel}%\nВыход на связь: " . GrammarHandler::timestampToDate($cottageInfo->data_receive_time) . "\nДата сбора показаний: " . GrammarHandler::timestampToDate($cottageInfo->last_indication_time) . "\nТемпература за бортом: {$dataInfo->externalTemperature}\nDevEui считывателя: {$cottageInfo->reader_id}\nКанал: {$cottageInfo->channel}\n/device_info_{$cottageInfo->reader_id}\n/current_{$cottageNumber}";
                    }
                }
                else{
                    return "Не смог определить номер участка. Команда-\"current {номер участка}\"";
                }
        }
        elseif(GrammarHandler::startsWith($msg_text, "/device_info_") && $isRegistered){
            return "Тут будет информация о считывателе";
        }
        return 'Не понимаю, о чём вы :( (вы написали ' . $msg_text . ')';
    }

    private static function sendMessage($messageText): void
    {
        self::$bot->sendMessage(self::$message->getChat()->getId(), $messageText);
    }
    private static function sendMessageToReceiver($receiver, $messageText): void
    {
        self::$bot->sendMessage($receiver, $messageText);
    }

    public static function notify(string $message)
    {
        $token = Info::TG_SERVICE_BOT_TOKEN;
        self::$bot = new Client($token);
        $subscribers = Telegram_service_clients::find()->all();
        if(!empty($subscribers)){
            foreach ($subscribers as $item) {
                self::sendMessageToReceiver($item->client_id, $message);
            }
        }
    }
}