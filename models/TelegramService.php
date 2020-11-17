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
                    self::sendMessage("Я работаю над этим");
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
        if ($msg_text === Info::TG_SERVICE_BOT_SECRET) {
            // зарегистрирую данного пользователя
            if(!Telegram_service_clients::isRegistered(self::$message->getChat()->getId())){
                (new Telegram_service_clients(['client_id' =>self::$message->getChat()->getId() ]))->save();
                return "Окей, теперь по команде /help доступен расширенный список команд";
            }
            return "Можно больше не вводить, ты уже зарегистрирован. По команде /help доступен расширенный список команд";
        }
        return 'Не понимаю, о чём вы :( (вы написали ' . $msg_text . ')';
    }

    private static function sendMessage($messageText): void
    {
        self::$bot->sendMessage(self::$message->getChat()->getId(), $messageText);
    }
}