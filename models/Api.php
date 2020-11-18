<?php /** @noinspection PhpUndefinedClassInspection */


namespace app\models;


use app\models\database\Alert;
use app\models\database\Blacklist_cottages;
use app\models\database\Blacklist_ips;
use app\models\database\Cottages;
use app\models\database\DefenceDevice;
use app\models\database\DefenceStatusChangeRequest;
use app\models\utils\AlertRawDataHandler;
use app\models\utils\RawDataHandler;
use app\models\utils\TimeHandle;
use Exception;
use JsonException;

class Api
{
    /**
     * @var User|null
     */
    private static ?User $user;

    /**
     * Обработка запроса
     * @return array|string[]
     */
    public static function handleRequest(): array
    {
        $text = file_get_contents('php://input');
        try {
            $data = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
            // проверю, не заблокирован ли ip
            $ipLoginError = Blacklist_ips::isLoginError();
            if ($ipLoginError) {
                return ['status' => 'failed', 'message' => $ipLoginError];
            }

            if (!empty($data['command'])) {
                switch ($data['command']) {
                    case 'login':
                        return self::login($data);
                    case 'get_current_status':
                        return self::get_current_status($data);
                    case 'change_alert_mode':
                        return self::change_alert_mode($data);
                    case 'request_data':
                        return self::request_data($data);
                    case 'inject_data':
                        return self::inject_data($data);
                    case 'incoming_alert':
                        return self::handle_incoming_alert($data);
                    case 'device_status_change_confirm':
                        return self::confirm_device_status_changes($data);
                    case 'check_alert_mode_changed':
                        return self::check_alert_mode_changed($data);
                    case 'alerts_handled':
                        return self::alerts_handled($data);
                }
            }
        } catch (JsonException $e) {
            return ['status' => 'failed, error found: ' . $e->getMessage(), 'data' => $text];
        }
        return ['status' => 'failed'];
    }

    /*    private static function token_valid($token): bool
        {
            $user = User::findIdentityByAccessToken($token);
            return $user !== null && $user->username === User::ADMIN_NAME;
        }*/
    private static function login($data): array
    {
        $login = $data['login'];
        $password = $data['password'];
        if (empty($login) || empty($password)) {
            return ['status' => 'failed', 'message' => 'empty login or password'];
        }
        $user = User::findByUsername($login);
        if ($user !== null) {
            $cottageLoginError = Blacklist_cottages::isLoginError($login);
            if ($cottageLoginError !== null) {
                return ['status' => 'failed', 'message' => $cottageLoginError];
            }
            if (!$user->validatePassword($password)) {
                Blacklist_cottages::registerWrongTry($login);
                Blacklist_ips::registerWrongTry();
                return ['status' => 'failed', 'message' => 'invalid login or password'];
            }
            // всё верно, верну токен идентификации
            Blacklist_cottages::clearTry($login);
            Blacklist_ips::clearTry();
            return ['status' => 'success', 'token' => $user->getAuthKey()];
        }
        Blacklist_cottages::registerWrongTry($login);
        Blacklist_ips::registerWrongTry();
        return ['status' => 'failed', 'message' => 'invalid login or password'];
    }

    private static function get_current_status($data): array
    {

        $accessControlResult = self::checkAccess($data);
        if (!empty($accessControlResult)) {
            return $accessControlResult;
        }
        $cottageInfo = Cottages::get(self::$user->cottage_number);
        $defenceStatus = 0;
        $alerts = [];
        if ($cottageInfo !== null) {
            if ($cottageInfo->binded_defence_device !== null) {
                $defenceDevice = DefenceDevice::findOne($cottageInfo->binded_defence_device);
                if ($defenceDevice !== null) {
                    $defenceStatus = $defenceDevice->status;
                    // проверю, есть ли тревоги, на которые не было реакции
                    $unhandledAlerts = Alert::findUnhandled($defenceDevice);
                    if (!empty($unhandledAlerts)) {
                        foreach ($unhandledAlerts as $unhandledAlert) {
                            $alerts[] = $unhandledAlert->raw_data;
                        }
                    }
                }
            }
            return ['status' => 'success', 'owner_io' => $cottageInfo->owner_personals, 'cottage_number' => $cottageInfo->cottage_number, 'current_status' => (bool)$defenceStatus, 'temp' => $cottageInfo->external_temperature, 'last_time' => $cottageInfo->last_indication_time, 'last_data' => $cottageInfo->current_counter_indication, 'alerts' => $alerts];
        }
        return ['status' => 'failed', 'message' => 'wrong token'];
    }

    private static function change_alert_mode($data): array
    {
        $accessControlResult = self::checkAccess($data);
        if (!empty($accessControlResult)) {
            return $accessControlResult;
        }
        $cottageInfo = Cottages::get(self::$user->cottage_number);
        if ($cottageInfo !== null) {
            // проверю, привязано ли к учётной записи устройство контроля
            if ($cottageInfo->binded_defence_device !== null) {
                $defenceDevice = DefenceDevice::findOne($cottageInfo->binded_defence_device);
                if ($defenceDevice !== null) {
                    // проверю, нет ли уже ожидающих подтверждения запросов
                    if (DefenceStatusChangeRequest::waitForConfirm($defenceDevice)) {
                        return ['status' => 'failed', 'message' => 'have waiting request'];
                    }
                    // зарегистрирую запрос
                    DefenceStatusChangeRequest::createNew($defenceDevice, $data['mode']);
                    return ['status' => 'success'];
                }
                return ['status' => 'failed', 'message' => 'no bound device found'];
            }
            return ['status' => 'failed', 'message' => 'device not bind'];
        }
        return ['status' => 'failed', 'message' => 'wrong token'];
    }

    private static function request_data($data): array
    {
        $accessControlResult = self::checkAccess($data);
        if (!empty($accessControlResult)) {
            return $accessControlResult;
        }
        $waitingDevices = [];
        $waitingDefenceChangeRequests = DefenceStatusChangeRequest::getWaitingForConfirm();
        if ($waitingDefenceChangeRequests !== null) {
            foreach ($waitingDefenceChangeRequests as $waitingDefenceChangeRequest) {
                $device = DefenceDevice::findOne(['id' => $waitingDefenceChangeRequest->device]);
                if ($device !== null) {
                    $waitingDevices[] = [
                        "id" => $waitingDefenceChangeRequest->id,
                        "devEui" => $device->devEui,
                        "port" => $device->port,
                        "status" => $waitingDefenceChangeRequest->requested_state
                    ];
                }
            }
        }
        // если есть необработанные тревоги- обработаю их
        $handledAlerts = [];
        if (!empty($data['alerts'])) {
            foreach ($data['alerts'] as $alert) {
                $rawData = $alert['raw_data'];
                $devEui = $alert['devEui'];
                self::handleAlert($rawData, $devEui);
                $handledAlerts[] = $rawData;
            }
        }
        return ['status' => 'success', 'defence_state_changes' => $waitingDevices, 'handled_alerts' => $handledAlerts];
    }

    private static function inject_data($data): array
    {
        $num = null;
        $changesCount = 0;
        $parsedValues = '';
        $accessControlResult = self::checkAccess($data);
        if (!empty($accessControlResult)) {
            return $accessControlResult;
        }
        $data = $data['data'];
        if (!empty($data)) {
            foreach ($data as $item) {
                // проверю, зарегистрирован ли участок на сервере
                if (!empty($item['cottageNumber'])) {
                    $num = $item['cottageNumber'];
                    $parsedValues .= $num . ' ';
                    $registeredCottage = Cottages::get($num);
                    if ($registeredCottage !== null) {
                        // проверю заряд батареи и данные считывателя. Если заряд ниже 80%
                        // или переданные значения меньше старых- оповещу
                        try{
                            $oldParsedInfo = new RawDataHandler($registeredCottage->last_raw_data);
                            $newParsedInfo = new RawDataHandler($registeredCottage->$item['rawData']);
                            if($newParsedInfo->batteryLevel < 80){
                                // оповещу
                                TelegramService::notify("Участок{$registeredCottage->cottage_number}: заряд считывателя:{$newParsedInfo->batteryLevel}%");
                            }
                            if($registeredCottage->current_counter_indication > (int)$item['currentData']){
                                TelegramService::notify("Участок{$registeredCottage->cottage_number}: Предыдущие показания({$registeredCottage->current_counter_indication}) больше новых{$item['currentData']}");
                            }
                        }
                        catch (Exception $e){

                        }
                        $changesCount++;
                        // запишу в карточку участка полученные данные
                        $registeredCottage->current_counter_indication = (int)$item['currentData'];
                        $registeredCottage->last_indication_time = (int)$item['indicationDate'];
                        $registeredCottage->external_temperature = (int)$item['outTemperature'];
                        $registeredCottage->last_raw_data = $item['rawData'];
                        $registeredCottage->data_receive_time = time();
                        $parsedValues .= 'handled ';
                        $registeredCottage->save();
                    } else {
                        // Зарегистрирую новый участок
                        $newCottage = new Cottages();
                        $newCottage->cottage_number = $num;
                        $newCottage->current_counter_indication = (int)$item['currentData'];
                        $newCottage->last_indication_time = (int)$item['indicationDate'];
                        $newCottage->external_temperature = (int)$item['outTemperature'];
                        $newCottage->last_raw_data = $item['rawData'];
                        $newCottage->data_receive_time = time();
                        $newCottage->save();
                    }
                }
            }
        }
        return ['status' => 'success', 'message' => 'data received', 'handled' => $changesCount, 'values' => $parsedValues];
    }

    /**
     * Проверка прав доступа
     * @param $data
     * @return string[]|null
     */
    private static function checkAccess($data): ?array
    {
        $token = $data['token'];
        if (empty($token)) {
            return ['status' => 'failed', 'message' => 'empty token'];
        }
        self::$user = User::findIdentityByAccessToken($token);
        if (self::$user === null) {
            Blacklist_ips::registerWrongTry();
            return ['status' => 'failed', 'message' => 'wrong token'];
        }
        return null;
    }

    private static function handle_incoming_alert($data): array
    {
        $accessControlResult = self::checkAccess($data);
        if (!empty($accessControlResult)) {
            return $accessControlResult;
        }
        $data = $data['data'];
        if (!empty($data)) {
            // разберу данные
            $devEui = $data['devEui'];
            $rawData = $data['rawData'];
            self::handleAlert($rawData, $devEui);
            return ['status' => 'success', 'message' => 'alert successful handled'];
        }
        return ['status' => 'failed', 'message' => 'can\'t find specified message'];
    }

    private static function confirm_device_status_changes($data): array
    {
        $accessControlResult = self::checkAccess($data);
        if (!empty($accessControlResult)) {
            return $accessControlResult;
        }
        // получу список идентификаторов зарегистрированных запросов
        $ips = $data['accepted'];
        $handledIps = '';
        if (!empty($ips)) {
            foreach ($ips as $ip) {
                // отмечу запрос как выполненный
                $request = DefenceStatusChangeRequest::findOne($ip);
                if ($request !== null) {
                    $request->is_accepted = 1;
                    $request->save();
                    // изменю значение защиты
                    $device = DefenceDevice::findOne($request->device);
                    if ($device !== null) {
                        $device->status = $request->requested_state;
                        $device->save();
                        // оповещу об изменении защиты через ботов
                        Telegram::confirmDefenceStatusChanged($device);
                        Viber::confirmDefenceStatusChanged($device);
                    }
                }
            }
        }
        return ['status' => "success", "message" => $handledIps];
    }

    private static function check_alert_mode_changed($data): array
    {
        $accessControlResult = self::checkAccess($data);
        if (!empty($accessControlResult)) {
            return $accessControlResult;
        }
        // получу привязанный девайс
        $cottageInfo = Cottages::get(self::$user->cottage_number);
        if ($cottageInfo !== null && $cottageInfo->binded_defence_device !== null) {
            // проверю наличие незавершённых запросов
            $deviceInfo = DefenceDevice::findOne($cottageInfo->binded_defence_device);
            if ($deviceInfo !== null) {
                if (DefenceStatusChangeRequest::waitForConfirm($deviceInfo)) {
                    return ['status' => "waiting", "message" => "wait for confirm"];
                }
                return ['status' => "success", "message" => "request done"];
            }
        }
        return ['status' => "success", "message" => "can't found bound device"];
    }

    /**
     * @param $rawData
     * @param $devEui
     */
    private static function handleAlert($rawData, $devEui): void
    {
        $handler = new AlertRawDataHandler($rawData);
        $message = '';
        $message .= "Получен сигнал тревоги со считывателя\n";
        $message .= "Идентификатор устройства: {$devEui}\n";
        $message .= "На входе:" . $handler->getActivePin() . "\n";
        $message .= "Статус контакта:" . $handler->getPinStatus($handler->getActivePin()) . "\n";
        $message .= "Время срабатывания:" . $handler->getAlertTime() . "\n";
        $message .= "Время получения тревоги:" . TimeHandle::timestampToDate(time()) . "\n";
        // получу номера участков, подписанных на это событие
        // найду сработавшее устройство
        $defenceDevice = DefenceDevice::findOne(['devEui' => $devEui, 'port' => $handler->getActivePin()]);
        if ($defenceDevice !== null) {
            // занесу данные о тревоге в базу
            (new Alert(['device' => $defenceDevice->id, 'alert_start_time' => time(), 'raw_data' => $rawData]))->save();
            // получу список участков, подписанных на это устройство
            $subscribers = Cottages::getSubscribers($defenceDevice);
        }
        if (!empty($subscribers)) {
            foreach ($subscribers as $subscriber) {
                Telegram::sendAlertMessage($message, $subscriber->cottage_number);
                Viber::sendAlertMessage($message, $subscriber->cottage_number);
            }
        }
    }

    private static function alerts_handled($data)
    {
        $accessControlResult = self::checkAccess($data);
        if (!empty($accessControlResult)) {
            return $accessControlResult;
        }
        $cottageInfo = Cottages::get(self::$user->cottage_number);
        if ($cottageInfo !== null && $cottageInfo->binded_defence_device !== null) {
            // проверю наличие незавершённых запросов
            $deviceInfo = DefenceDevice::findOne($cottageInfo->binded_defence_device);
            if ($deviceInfo !== null) {
                if (Alert::setConfirmed($deviceInfo)) {
                    return ['status' => "waiting", "message" => "wait for confirm"];
                }
            }
        }
        return ['status' => "failed", "message" => "something wrong"];
    }
}