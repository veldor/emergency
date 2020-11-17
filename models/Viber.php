<?php


namespace app\models;


use app\models\database\Blacklist_cottages;
use app\models\database\Blacklist_viber;
use app\models\database\Cottages;
use app\models\database\DefenceDevice;
use app\models\database\DefenceStatusChangeRequest;
use app\models\database\Viber_clients;
use app\models\utils\GrammarHandler;
use app\priv\Info;
use Exception;
use phpDocumentor\Reflection\Types\False_;
use Viber\Api\Event;
use Viber\Api\Keyboard;
use Viber\Api\Keyboard\Button;
use Viber\Api\Message\Text;
use Viber\Api\Sender;
use Viber\Bot;
use Viber\Client;
use yii\base\Model;

class Viber extends Model
{

    public const CONCLUSIONS = 'заключения';
    public const EXECUTIONS = 'файлы';
    public const DOWNLOADS_COUNT = 'статистика загрузок';
    public const VIBER_FILE_SIZE_LIMIT = 52428800;

    /**
     * @param $apiKey
     * @return Bot
     */
    public static function getBot($apiKey): Bot
    {
        return new Bot(['token' => $apiKey]);
    }

    /**
     * @return Sender
     */
    public static function getBotSender(): Sender
    {
        return new Sender([
            'name' => 'Бот-защитник "Облепихи"',
            'avatar' => 'https://rdcnn.ru/images/bot.png',
        ]);
    }

    private static Bot $bot;
    private static Sender $botSender;
    private static string $receiverId;


    /**
     * регистрация хука
     */
    public static function setup(): void
    {

        $apiKey = Info::VIBER_API_KEY;
        $webHookUrl = 'https://oblepiha.site/viber/connect';
        try {
            $client = new Client(['token' => $apiKey]);
            $client->setWebhook($webHookUrl);
            echo "Success!\n";
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage() . "\n";
        }
    }

    public static function handleRequest(): void
    {
        self::$bot = new Bot(['token' => Info::VIBER_API_KEY]);
        self::$botSender = new Sender([
            'name' => 'Бот РДЦ',
            'avatar' => 'https://oblepiha.site//images/mini_logo.png',
        ]);
        self::$bot
            ->onConversation(function () {
                return (new Text())
                    ->setSender(self::$botSender)
                    ->setText('Добрый день. Я бот РДЦ. Выберите, что вы хотите сделать')
                    ->setKeyboard(
                        (new Keyboard())
                            ->setButtons([
                                (new Button())
                                    ->setBgColor('#2fa4e7')
                                    ->setTextHAlign('center')
                                    ->setActionType('reply')
                                    ->setActionBody('authentication')
                                    ->setText('Представиться'),

                            ])
                    );
            })
            ->onText(/**
             * @param $event Event
             */ '|authentication|s', static function ($event) {
                self::$receiverId = $event->getSender()->getId();
                self::$bot->getClient()->sendMessage(
                    (new Text())
                        ->setSender(self::$botSender)
                        ->setReceiver(self::$receiverId)
                        ->setText('Напишите "вход", затем через пробел номер участка и пароль. Например, "вход 1 123456')
                );
            })
            ->onText(/**
             * @param $event Event
             */ '|enable alert|s', static function ($event) {
                self::$receiverId = $event->getSender()->getId();
                self::enableAlert();
            })
            ->onText(/**
             * @param $event Event
             */ '|disable alert|s', static function ($event) {
                self::$receiverId = $event->getSender()->getId();
                self::disableAlert();
            })
            ->onText(/**
             * @param $event Event
             */ '|alert status|s', static function ($event) {
                self::$receiverId = $event->getSender()->getId();
                self::getAlertStatus();
            })
            ->onText('|.+|s', static function ($event) {
                self::$receiverId = $event->getSender()->getId();
                $text = $event->getMessage()->getText();
                self::handleTextRequest($text);
            })
            ->run();
    }

    /**
     * Отправляю ссобщение пользователю
     * @param $text
     */
    private static function sendMessage($text): void
    {
        self::$bot->getClient()->sendMessage(
            (new Text())
                ->setSender(self::$botSender)
                ->setReceiver(self::$receiverId)
                ->setText($text)
        );
    }

    /**
     * Отправляю ссобщение пользователю
     * @param $text
     */
    private static function sendMessageToPerson($text, $personId): void
    {
        self::$bot->getClient()->sendMessage(
            (new Text())
                ->setSender(self::$botSender)
                ->setReceiver($personId)
                ->setText($text)
        );
    }

    /**
     * Отправляю ссобщение пользователю
     * @param $text
     */
    private static function sendMessageWithButtonsToPerson($text, array $buttons, $personId): void
    {
        self::$bot->getClient()->sendMessage(
            (new Text())
                ->setSender(self::$botSender)
                ->setReceiver($personId)
                ->setText($text)
                ->setKeyboard(
                    (new Keyboard())
                        ->setButtons($buttons)
                )
        );
    }

    private static function handleTextRequest($text): void
    {
        $lowerText = mb_strtolower($text);
        if ($lowerText === 'представиться') {
            self::sendMessage('Напишите "вход", затем через пробел номер участка и пароль. Например, "вход 1 123456');
        } else if ($lowerText === 'включить защиту') {
            self::enableAlert();
        } else if ($lowerText === 'отключить защиту') {
            self::disableAlert();
        } else if ($lowerText === 'текущий статус защиты') {
            self::getAlertStatus();
        } else if (GrammarHandler::startsWith($lowerText, 'вход')) {
            // выполню вход
            self::login($text);
            return;
        } else {
            self::sendMessage('Для того, чтобы пользоваться возможностями бота, напишите "вход", затем через пробел номер участка и пароль. Например, "вход 1 123456"');
        }
    }

    private static function login(string $command): void
    {
        $pattern = '/^вход\s+(\S+)\s+(\S+)\s*$/iu';
        $matches = null;
        if (preg_match($pattern, $command, $matches)) {
            // проверю, что пользователь не заблокирован
            $cottageLoginError = Blacklist_cottages::isLoginError($matches[1]);
            $telegramLoginError = Blacklist_viber::isLoginError(self::$receiverId);
            if ($cottageLoginError !== null) {
                self::sendMessage($cottageLoginError);
                return;
            }
            if ($telegramLoginError) {
                self::sendMessage($telegramLoginError);
                return;
            }
            $user = User::findByUsername($matches[1]);
            if ($user === null) {
                Blacklist_viber::registerWrongTry(self::$receiverId);
                self::sendMessage('Неверный логин или пароль 1');
            }
            if (!$user->validatePassword($matches[2])) {
                Blacklist_cottages::registerWrongTry($matches[1]);
                Blacklist_viber::registerWrongTry(self::$receiverId);
                self::sendMessage('Неверный логин или пароль (' . $matches[2] . ')');
                return;
            }
            // если данные верные- привяжу участок к аккаунту
            Viber_clients::bind(self::$receiverId, $matches[1]);
            Blacklist_viber::resetTry(self::$receiverId);
            self::sendControlMessage($matches[1]);
        }
    }

    private static function sendMessageWithButtons(string $text, array $buttons): void
    {
        self::$bot->getClient()->sendMessage(
            (new Text())
                ->setSender(self::$botSender)
                ->setReceiver(self::$receiverId)
                ->setText($text)
                ->setKeyboard(
                    (new Keyboard())
                        ->setButtons($buttons)
                )
        );
    }

    private static function sendControlMessage($cottageId): void
    {
        $existentCottage = Cottages::findOne(['cottage_number' => $cottageId]);
        if ($existentCottage !== null) {
            // проверю статус защиты
            if ($existentCottage->alert_status) {
                self::sendMessageWithButtons(
                    GrammarHandler::getUserIo($existentCottage->owner_personals) . ' защита участка на данный момент включена. Вы можете отключить её, нажав на кнопку "Отключить защиту", или отправив команду "Отключить защиту"',
                    self::getDisableAlertButtons()
                );
            } else {
                self::sendMessageWithButtons(
                    GrammarHandler::getUserIo($existentCottage->owner_personals) . ' защита участка на данный момент отключена. Вы можете включить её, нажав на кнопку "Включить защиту", или отправив команду "Включить защиту"',
                    self::getEnableAlertButtons()
                );
            }
        }
        // отправлю сообщение с кнопками для управления статусом защиты

    }

    private static function getEnableAlertButtons(): array
    {
        return [(new Button())
            ->setBgColor('#2fa4e7')
            ->setTextHAlign('center')
            ->setActionType('reply')
            ->setActionBody('enable alert')
            ->setText('Включить защиту'),
            (new Button())
                ->setBgColor('#2fa4e7')
                ->setTextHAlign('center')
                ->setActionType('reply')
                ->setActionBody('alert status')
                ->setText('Текущий статус защиты'),
        ];
    }

    private static function getDisableAlertButtons(): array
    {
        return [(new Button())
            ->setBgColor('#2fa4e7')
            ->setTextHAlign('center')
            ->setActionType('reply')
            ->setActionBody('disable alert')
            ->setText('Отключить защиту'),
            (new Button())
                ->setBgColor('#2fa4e7')
                ->setTextHAlign('center')
                ->setActionType('reply')
                ->setActionBody('alert status')
                ->setText('Текущий статус защиты'),
        ];
    }

    private static function enableAlert(): void
    {
        // проверю, что пользователь подключен к системе и защита включена
        $user = Viber_clients::get(self::$receiverId);
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
                        'Отправлен запрос на включение защиты, ожидаю подтверждения от сервера'
                    );
                }
            }
        }
    }

    private static function disableAlert(): void
    {
        // проверю, что пользователь подключен к системе и защита включена
        $user = Viber_clients::get(self::$receiverId);
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

    private static function getAlertStatus(): void
    {
        // проверю, что пользователь подключен к системе и защита включена
        $user = Viber_clients::get(self::$receiverId);
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
                    self::sendMessageWithButtons(
                        $text . "Защита отключена",
                        self::getEnableAlertButtons()
                    );
                } else {
                    self::sendMessageWithButtons(
                        $text . "Защита включена",
                        self::getDisableAlertButtons()
                    );
                }
            }
        }
    }

    public static function sendBroadcast(string $message)
    {
        // получу все контакты из листа
        $contacts = Viber_clients::getAll();
        self::$bot = new Bot(['token' => Info::VIBER_API_KEY]);
        self::$botSender = new Sender([
            'name' => 'Бот-защитник "Облепихи"',
            'avatar' => 'https://oblepiha.site//images/mini_logo.png',
        ]);
        if (!empty($contacts)) {
            foreach ($contacts as $item) {
                self::sendMessageToPerson($message, $item->client_id);
            }
        }
    }

    public static function sendAlertMessage(string $message, string $cottage_number)
    {
        self::$bot = new Bot(['token' => Info::VIBER_API_KEY]);
        self::$botSender = new Sender([
            'name' => 'Бот-защитник "Облепихи"',
            'avatar' => 'https://oblepiha.site//images/mini_logo.png',
        ]);
        // получу подписчиков
        $subscribers = Viber_clients::findAll(['client_cottage_number' => $cottage_number]);
        if (!empty($subscribers)) {
            foreach ($subscribers as $subscriber) {
                self::sendMessageToPerson($message, $subscriber->client_id);
            }
        }
    }

    public static function confirmDefenceStatusChanged(DefenceDevice $device)
    {
        self::$bot = new Bot(['token' => Info::VIBER_API_KEY]);
        self::$botSender = new Sender([
            'name' => 'Бот-защитник "Облепихи"',
            'avatar' => 'https://oblepiha.site//images/mini_logo.png',
        ]);
        $cottages = Cottages::getSubscribers($device);
        if (!empty($cottages)) {
            $message = $device->status ? "Изменился статус защиты на \"Активна\"" : "Изменился статус защиты на \"Отключена\"";
            foreach ($cottages as $cottage) {
                $subscribers = Viber_clients::findAll(['client_cottage_number' => $cottage->cottage_number]);
                if (!empty($subscribers)) {
                    foreach ($subscribers as $subscriber) {
                        self::sendMessageWithButtonsToPerson($message, ($device->status ? self::getDisableAlertButtons() : self::getEnableAlertButtons()), $subscriber->client_id);
                    }
                }
            }
        }
    }
}
