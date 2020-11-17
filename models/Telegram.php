<?php /** @noinspection PhpUndefinedMethodInspection */


namespace app\models;


use app\models\database\Blacklist_cottages;
use app\models\database\Blacklist_telegram;
use app\models\database\Cottages;
use app\models\database\DefenceDevice;
use app\models\database\DefenceStatusChangeRequest;
use app\models\database\Telegram_clients;
use app\models\utils\GrammarHandler;
use app\priv\Info;
use Exception;
use TelegramBot\Api\Client;
use TelegramBot\Api\InvalidJsonException;
use TelegramBot\Api\Types\Message;
use TelegramBot\Api\Types\ReplyKeyboardMarkup;
use TelegramBot\Api\Types\Update;

class Telegram
{
    private static Client $bot;
    private static Message $message;

    public static function handleRequest(): void
    {

        try {
            $token = Info::TG_BOT_TOKEN;
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
                    if (Telegram_clients::isCottageSigned($message->getChat()->getId())) {
                        $answer = 'Команды:
/help - вывод справки
/enable - активировать защиту
/disable - отключить защиту
/status - статус защиты';
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
// команда отключения защиты
            self::$bot->command('disable', static function ($message) {
                self::$message = $message;
                try {
                    self::disableAlert();
                } catch (Exception $e) {
                    self::sendMessage($e->getMessage());
                }
            });
// команда включения защиты
            self::$bot->command('enable', static function ($message) {
                self::$message = $message;
                try {
                    self::enableAlert();
                } catch (Exception $e) {
                    self::sendMessage($e->getMessage());
                }
            });
// команда получения текущего статуса
            self::$bot->command('status', static function ($message) {
                self::$message = $message;
                try {
                    self::getAlertStatus();
                } catch (Exception $e) {
                    self::sendMessage($e->getMessage());
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
        if (GrammarHandler::startsWith($msg_text, 'вход')) {
            // разберу попытку входа
            return self::login($msg_text);
        }
        return 'Не понимаю, о чём вы :( (вы написали ' . $msg_text . ')';
    }

    private static function login(string $msg_text): string
    {
        $pattern = '/^вход\s+(\S+)\s+(\S+)\s*$/iu';
        $matches = null;
        if (preg_match($pattern, $msg_text, $matches)) {
            // проверю, что пользователь не заблокирован
            $cottageLoginError = Blacklist_cottages::isLoginError($matches[1]);
            $telegramLoginError = Blacklist_telegram::isLoginError(self::$message->getChat()->getId());
            if ($cottageLoginError !== null) {
                return $cottageLoginError;
            }
            if ($telegramLoginError) {
                return $telegramLoginError;
            }
            $user = User::findByUsername($matches[1]);
            if ($user === null) {
                Blacklist_telegram::registerWrongTry(self::$message->getChat()->getId());
                return 'Неверный логин или пароль';
            }
            if (!$user->validatePassword($matches[2])) {
                Blacklist_cottages::registerWrongTry($matches[1]);
                Blacklist_telegram::registerWrongTry(self::$message->getChat()->getId());
                return 'Неверный логин или пароль';
            }
            // если данные верные- привяжу участок к аккаунту
            Telegram_clients::bind(self::$message->getChat()->getId(), $matches[1]);
            Blacklist_telegram::resetTry(self::$message->getChat()->getId());
            return 'Участок ' . $matches[1] . ' успешно привязан к аккаунту';
        }
        return 'Команда не распознана';
    }

    private static function disableAlert(): void
    {
        // проверю, что пользователь подключен к системе и защита включена
        $user = Telegram_clients::get(self::$message->getChat()->getId());
        if ($user !== null && !empty($user->client_cottage_number)) {
            $boundCottage = Cottages::get($user->client_cottage_number);
            if ($boundCottage !== null && $boundCottage->binded_defence_device !== null) {
                $defenceDevice = DefenceDevice::getCottageDevice($boundCottage);
                if ($defenceDevice !== null) {
                    // проверю, нет ли неподтвержённых изменений защиты в очереди
                    if (DefenceStatusChangeRequest::waitForConfirm($defenceDevice)) {
                        self::sendMessageWithButtons(
                            'Ожидаю подтверждение предыдущего запроса, изменение статуса невозможно',
                            self::getEnableAlertButtons()
                        );
                        return;
                    }
                    // добавлю ожидающий запрос
                    DefenceStatusChangeRequest::createNew($defenceDevice, false);
                    self::sendMessage(
                        'Отправлен запрос на отключение защиты, ожидаю подтверждения от сервера'
                    );
                }
            }
        }
    }

    private static function enableAlert(): void
    {
        // проверю, что пользователь подключен к системе и защита включена
        $user = Telegram_clients::get(self::$message->getChat()->getId());
        if ($user !== null && !empty($user->client_cottage_number)) {
            $boundCottage = Cottages::get($user->client_cottage_number);
            if ($boundCottage !== null && $boundCottage->binded_defence_device !== null) {
                $defenceDevice = DefenceDevice::getCottageDevice($boundCottage);
                if ($defenceDevice !== null) {
                    // проверю, нет ли неподтвержённых изменений защиты в очереди
                    if (DefenceStatusChangeRequest::waitForConfirm($defenceDevice)) {
                        self::sendMessageWithButtons(
                            'Ожидаю подтверждение предыдущего запроса, изменение статуса невозможно',
                            self::getEnableAlertButtons()
                        );
                        return;
                    }
                    // добавлю ожидающий запрос
                    DefenceStatusChangeRequest::createNew($defenceDevice, true);
                    self::sendMessage(
                        'Отправлен запрос на активацию защиты, ожидаю подтверждения от сервера'
                    );
                }
            }
        }
    }

    private static function getAlertStatus(): void
    {
        // проверю, что пользователь подключен к системе и защита включена
        $user = Telegram_clients::get(self::$message->getChat()->getId());
        if ($user !== null && !empty($user->client_cottage_number)) {
            $boundCottage = Cottages::get($user->client_cottage_number);
            if ($boundCottage !== null) {
                $text = '';
                if ($boundCottage->external_temperature !== null) {
                    $text .= 'Температура на улице: ' . GrammarHandler::simpleConvertTemperature($boundCottage->external_temperature) . "\n";
                }
                if ($boundCottage->last_indication_time !== null) {
                    $text .= 'Время последнего выхода счётчика на связь: ' . GrammarHandler::timestampToDate($boundCottage->last_indication_time) . "\n";
                }
                if ($boundCottage->current_counter_indication !== null) {
                    $text .= 'Последние показания счётчика: ' . GrammarHandler::handleCounterData($boundCottage->current_counter_indication) . "\n";
                }
                if ($boundCottage->alert_status === 0) {
                    // защита отключена
                    $keyboard = new ReplyKeyboardMarkup(array(array("/enable")), true);
                    self::sendMessageWithButtons($text . "Защита отключена", $keyboard);
                } else {
                    $keyboard = new ReplyKeyboardMarkup(array(array("/disable")), true);
                    self::sendMessageWithButtons($text . "Защита включена", $keyboard);
                }

            }
        }
    }

    private static function sendMessage($messageText): void
    {
        self::$bot->sendMessage(self::$message->getChat()->getId(), $messageText);
    }

    private static function sendMessageToPerson($messageText, $personId): void
    {
        self::$bot->sendMessage($personId, $messageText);
    }

    private static function sendMessageWithKeyboardToPerson($messageText, $keyboard, $personId): void
    {
        self::$bot->sendMessage($personId, $messageText, null, false, null, $keyboard);
    }

    private static function sendMessageWithButtons($messageText, $keyboard): void
    {
        self::$bot->sendMessage(self::$message->getChat()->getId(), $messageText, null, false, null, $keyboard);
    }

    public static function sendBroadcast(string $message)
    {
        // получу все контакты из листа
        $contacts = Telegram_clients::getAll();
        $token = Info::TG_BOT_TOKEN;
        self::$bot = new Client($token);
        if (!empty($contacts)) {
            foreach ($contacts as $contact) {
                self::sendMessageToPerson($message, $contact->client_id);
            }
        }
    }

    public static function sendAlertMessage(string $message, string $cottage_number): void
    {
        // получу подписчиков
        $subscribers = Telegram_clients::findAll(['client_cottage_number' => $cottage_number]);
        if (!empty($subscribers)) {
            $token = Info::TG_BOT_TOKEN;
            self::$bot = new Client($token);
            foreach ($subscribers as $subscriber) {
                self::sendMessageToPerson($message, $subscriber->client_id);
            }
        }
    }

    public static function confirmDefenceStatusChanged(database\DefenceDevice $device): void
    {
        $token = Info::TG_BOT_TOKEN;
        self::$bot = new Client($token);
        $cottages = Cottages::getSubscribers($device);
        if (!empty($cottages)) {
            $message = $device->status ? "Изменился статус защиты на \"Активна\"" : "Изменился статус защиты на \"Отключена\"";
            foreach ($cottages as $cottage) {
                $subscribers = Telegram_clients::getSubscribers($cottage);
                if (!empty($subscribers)) {
                    foreach ($subscribers as $subscriber) {
                        self::sendMessageWithKeyboardToPerson($message, ($device->status ? self::getDisableAlertButtons() : self::getEnableAlertButtons()), $subscriber->client_id);
                    }
                }
            }
        }
    }

    private static function getEnableAlertButtons(): ReplyKeyboardMarkup
    {
        return new ReplyKeyboardMarkup(array(array("/enable", "/status")), true);
    }

    private static function getDisableAlertButtons(): ReplyKeyboardMarkup
    {
        return new ReplyKeyboardMarkup(array(array("/disable", "/status")), true);
    }
}