<?php /** @noinspection PhpUndefinedMethodInspection */


namespace app\models;


use app\models\database\Cottages;
use app\models\database\Telegram_service_clients;
use app\models\utils\GrammarHandler;
use app\models\utils\PingChecker;
use app\models\utils\RawDataHandler;
use app\models\utils\TimeHandle;
use app\priv\Info;
use Exception;
use TelegramBot\Api\Client;
use TelegramBot\Api\InvalidJsonException;
use TelegramBot\Api\Types\Message;
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
/ping - последний выход сервера на связь
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
// команда проверки выхода сервера на связь
            self::$bot->command('ping', static function ($message) {
                self::$message = $message;
                $ping = (new PingChecker())->getPing();
                self::sendMessage(TimeHandle::timestampToDate($ping));
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

        if ($isRegistered && (GrammarHandler::startsWith($msg_text, "current") || GrammarHandler::startsWith($msg_text, "/c_"))) {
            $valuesArr = explode(" ", $msg_text);
            if(!empty($valuesArr) && count($valuesArr) === 2){
                $cottageNumber =  $valuesArr[1];
            }
            else{
                $cottageNumber = substr($msg_text, 3);
            }
                if(!empty($cottageNumber)){
                    /** @var Cottages $cottageInfo */
                    $cottageInfo = Cottages::findOne(['cottage_number' => $cottageNumber]);
                    if($cottageInfo !== null){
                        try {
                            $dataInfo = new RawDataHandler($cottageInfo->last_raw_data);
                        } catch (exceptions\InvalidParamException $e) {
                            return "error handle raw params " . $e->getMessage();
                        }
                        $valueName = 'pin_' . $cottageInfo->channel . '_value';
                        $counted = $dataInfo->$valueName;
                        $startValue = (float)$cottageInfo->initial_value;
                        $total = round($counted + $startValue , 3);
                        return "
Участок: $cottageNumber
Начальное значение: $startValue Квт*ч
Считыватель насчитал: {$counted} Квт*ч
Итого: {$total} Квт*ч
Заряд батареи: {$dataInfo->batteryLevel}%
Выход на связь: " . GrammarHandler::timestampToDate($cottageInfo->data_receive_time) . "
Показания собраны: " . GrammarHandler::timestampToDate($cottageInfo->last_indication_time) . "
Температура за бортом: {$dataInfo->externalTemperature}
DevEui считывателя: {$cottageInfo->reader_id}
Канал: {$cottageInfo->channel}
/d_{$cottageInfo->reader_id}
/c_{$cottageNumber}";
                    }
                }
                else{
                    return "Не смог определить номер участка. Команда-\"current {номер участка}\"";
                }
        } elseif(GrammarHandler::startsWith($msg_text, "/d_") && $isRegistered){
            $deviceId = substr($msg_text, 3);
            $counterRawData = Cottages::getCounterRawData($deviceId);
            if(empty($counterRawData)){
                return "Данные об устройстве пока недоступны " . $deviceId;
            }
            try {
                $handler = new RawDataHandler($counterRawData);
                $answer = '';
                $answer.= "Считыватель " . $deviceId . "\n";
                if(!empty($handler->batteryLevel)){
                    $answer .= "Уровень заряда батареи: {$handler->batteryLevel}%\n";
                }
                if(!empty($handler->activationType)){
                    $answer .= "Тип активации: {$handler->activationType}\n";
                }
                if(!empty($handler->pingInterval)){
                    $answer .= "Таймаут опроса: {$handler->pingInterval}\n";
                }
                if(!empty($handler->externalTemperature)){
                    $answer .= "Внешняя температура: {$handler->externalTemperature}\n";
                }
                if(!empty($handler->indicationTime)){
                    $answer .= "Данные собраны: " . GrammarHandler::timestampToDate($handler->indicationTime) . "\n";
                }
                if(!empty($handler->pin_1_type)){
                    $answer .= "Тип входа 1: {$handler->pin_1_type}\n";
                }
                if(!empty($handler->pin_1_value)){
                    $answer .= "Показания входа 1: {$handler->pin_1_value}\n";
                }
                if(!empty($handler->pin_2_type)){
                    $answer .= "Тип входа 2: {$handler->pin_2_type}\n";
                }
                if(!empty($handler->pin_2_value)){
                    $answer .= "Показания входа 2: {$handler->pin_2_value}\n";
                }
                if(!empty($handler->pin_3_type)){
                    $answer .= "Тип входа 3: {$handler->pin_3_type}\n";
                }
                if(!empty($handler->pin_3_value)){
                    $answer .= "Показания входа 3: {$handler->pin_3_value}\n";
                }
                if(!empty($handler->pin_4_type)){
                    $answer .= "Тип входа 4: {$handler->pin_4_type}\n";
                }
                if(!empty($handler->pin_4_value)){
                    $answer .= "Показания входа 4: {$handler->pin_4_value}\n";
                }
                // получу номера участков, привязанные к считывателю
                $boundedCottages = Cottages::getBoundedCottages($deviceId);
                if(!empty($boundedCottages)){
                    $answer .= "Участки на устройстве:\n";
                    foreach ($boundedCottages as $boundedCottage) {
                        $answer .= "/c_{$boundedCottage->cottage_number}\n";
                    }
                }
                return $answer;
            } catch (exceptions\InvalidParamException $e) {
                return "не смог обработать данные считывателя";
            }
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

    public static function notify(string $message): void
    {
        $token = Info::TG_SERVICE_BOT_TOKEN;
        self::$bot = new Client($token);
        /** @var Telegram_service_clients[] $subscribers */
        $subscribers = Telegram_service_clients::find()->all();
        if(!empty($subscribers)){
            foreach ($subscribers as $item) {
                self::sendMessageToReceiver($item->client_id, $message);
            }
        }
    }
}