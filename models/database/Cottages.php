<?php


namespace app\models\database;


use app\models\exceptions\InvalidParamException;
use app\models\User;
use app\models\utils\RawDataHandler;
use app\models\utils\TimeHandle;
use Yii;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;

/**
 * @property string $cottage_number [varchar(10)]  Номер участка
 * @property int $id [int(11) unsigned]
 * @property string $owner_personals [varchar(255)]  имя владельца
 * @property bool $alert_status [tinyint(1)]  статус защиты
 * @property int $external_temperature [int(11)]  Температура на улице
 * @property int $current_counter_indication [int(11)]  Последние показания счётчика
 * @property int $last_indication_time [bigint(20) unsigned]  Время снятия последних показаний
 * @property string $last_raw_data [char(48)]  Сырые данные счётчика
 * @property int $data_receive_time [int(10) unsigned]  Дата получения показаний
 * @property int $binded_defence_device [int(10) unsigned]  Привязанное устройство
 * @property bool $subscribe_broadcast [tinyint(1)]  Подписка на все события
 * @property string $initial_value [varchar(10)]  Начальные показания
 * @property int $channel [smallint(1) unsigned]  Канал считывателя
 * @property int $reader_power_state [smallint(1) unsigned]  Канал считывателя
 * @property string $reader_id varchar(16)]  Идентификатор считывателя
 */
class Cottages extends ActiveRecord
{
    public const SPEND = 172800;

    /**
     * @param $id
     * @return array
     * @throws \Throwable
     * @throws StaleObjectException
     */
    public static function deleteCottage($id): array
    {
        $existentCottage = self::findOne(['cottage_number' => $id]);
        if ($existentCottage !== null) {
            $existentPerson = User::findByUsername($existentCottage->cottage_number);
            if ($existentPerson !== null) {
                $authRule = Auth_assignment::findOne(['user_id' => $existentPerson->id]);
                if ($authRule !== null) {
                    $authRule->delete();
                }
                $existentPerson->delete();
            }
            $existentCottage->delete();
            return ['status' => 1, 'header' => 'Успешно', 'message' => 'Участок удалён.', 'reload' => true];
        }
        return ['status' => 2, 'message' => 'Участок не найден.'];
    }

    /**
     * @param $action
     * @return string
     */
    public static function doAlertAction($action): string
    {
        $existentCottage = self::get(Yii::$app->user->identity->cottage_number);
        if ($existentCottage !== null) {
            if ($action === 'enable') {
                if ($existentCottage->alert_status) {
                    return 'Защита уже включена';
                }

                $existentCottage->alert_status = 1;
                $existentCottage->save();
                return 'Защита включена';
            }

            if ($action === 'disable') {
                if (!$existentCottage->alert_status) {
                    return 'Защита уже отключена';
                }

                $existentCottage->alert_status = 0;
                $existentCottage->save();
                return 'Защита отключена';
            }
        }
        return 'Не удалось обнаружить участок';
    }

    /**
     * Получение данных о состоянии защиты зарегистрированных в программе участков
     * @return array
     */
    public static function getDefenceStatus()
    {
        $answer = [];
        $users = User::find()->all();
        if (!empty($users)) {
            /** @var User[] $users */
            foreach ($users as $item) {
                $cottage = self::findOne(['cottage_number' => $item->cottage_number]);
                if ($cottage !== null) {
                    $answer[] = [$cottage->cottage_number, $cottage->alert_status];
                }
            }
        }
        return $answer;
    }

    /**
     * @param DefenceDevice $defenceDevice
     * @return Cottages[]
     */
    public static function getSubscribers(DefenceDevice $defenceDevice): array
    {
        return self::find()->where(['binded_defence_device' => $defenceDevice->id])->orWhere(['subscribe_broadcast' => 1])->all();
    }

    /**
     * @param $devEui
     * @param $state
     */
    public static function setPowerState($devEui, $state): void
    {
        /** @var Cottages[] $cottages */
        $cottages = self::find()->where(['reader_id' => $devEui])->all();
        if (!empty($cottages)) {
            foreach ($cottages as $cottage) {
                $cottage->reader_power_state = $state;
                $cottage->save();
            }
        }
    }

    public static function changeInitialValue($cottageNumber, $newInitialValue): void
    {
        $cottage = self::findOne(['cottage_number' => $cottageNumber]);
        if ($cottage !== null) {
            $cottage->initial_value = str_replace(',', '.', $newInitialValue);
            $cottage->save();
        }
    }

    /**
     * Проверка на присуствие участка в базе
     * @param string $cottage_number
     * @return bool
     */
    private static function exists(string $cottage_number): bool
    {
        return (bool)self::find()->where(['cottage_number' => $cottage_number])->count();
    }

    public function rules(): array
    {
        return [
            [['cottage_number', 'owner_personals'], 'safe'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'cottage_number' => 'Номер участка',
            'owner_personals' => 'ФИО владельца'
        ];
    }

    public static function tableName(): string
    {
        return 'cottages';
    }

    public static function get(string $cottage_number)
    {
        return self::findOne(['cottage_number' => $cottage_number]);
    }

    /**
     * @return Cottages[]
     */
    public static function getAll(): array
    {
        return self::find()->orderBy('cottage_number')->all();
    }

    public function register(): ?array
    {
        $existentCottage = self::get($this->cottage_number);
        if ($existentCottage === null) {
            $existentCottage = new self(['owner_personals' => trim($this->owner_personals), 'cottage_number' => trim($this->cottage_number)]);
        }
        $existentCottage->owner_personals = $this->owner_personals;
        $existentCottage->save();
        $existentUser = User::findByUsername($this->cottage_number);
        if ($existentUser === null) {
            $password = User::registerNew($existentCottage);
            return ['status' => 1, 'header' => 'Успешно', 'message' => 'Участок зарегистрирован. Пароль: ' . $password, 'reload' => true];

        }
        return ['status' => 2, 'message' => 'Кажется, этот участок уже зарегистрирован'];
    }

    public static function checkLost()
    {
        // получу данные обо всех участках
        /** @var Cottages[] $cottagesData */
        $cottagesData = self::find()->all();
        $cottageInfo = null;
        $errors = [];
        if (!empty($cottagesData)) {
            foreach ($cottagesData as $item) {
                try {
                    $cottageInfo = new RawDataHandler($item->last_raw_data);
                    // участок должен был выйти на связь не позднее чем за двое суток от текущей даты
                    $spend = time() - self::SPEND;
                    if ($item->data_receive_time < $spend) {
                        $errors[] = "Считыватель участка /c_{$item->cottage_number} (/d_{$item->reader_id}) не выходил на связь с " . TimeHandle::timestampToDate($item->data_receive_time) . "\n";
                    }
                    if ($cottageInfo->batteryLevel < 80) {
                        $errors[] = "Считыватель участка /c_{$item->cottage_number} (/d_{$item->reader_id}): заряд батареи {$cottageInfo->batteryLevel}%\n";
                    }
                } catch (InvalidParamException $e) {
                }
            }
        }
        if (!empty($errors)) {
            $answer = '';
            foreach ($errors as $error) {
                $answer .= $error;
            }
            return $answer;
        }
        return 'Артефактов не найдено, всё в порядке.';
    }

    /**
     * @param string $deviceId
     * @return string|null
     */
    public static function getCounterRawData(string $deviceId): ?string
    {
        /** @var Cottages|null $result */
        $result = self::find()->where(['reader_id' => $deviceId])->one();
        if ($result !== null) {
            return $result->last_raw_data;
        }
        return null;
    }

    /**
     * @param string $deviceId
     * @return Cottages[]
     */
    public static function getBoundedCottages(string $deviceId): array
    {
        return self::find()->where(['reader_id' => $deviceId])->all();
    }

    public static function getCounterPowerState(string $deviceId): string
    {
        /** @var Cottages $result */
        $result = self::find()->where(['reader_id' => $deviceId])->one();
        if ($result !== null) {
            return $result->reader_power_state ? 'внешнее питание' : 'от батареи';
        }
    }

    public static function getCountersInfo(): string
    {
        $result = 'no data';
        $cottages = self::find()->all();
        $counters = [];
        if (!empty($cottages)) {
            /** @var Cottages $cottage */
            foreach ($cottages as $cottage) {
                $counters[$cottage->reader_id] = $cottage->data_receive_time;
            }
        }
        if (!empty($counters)) {
            asort($counters);
            $result = '';
            foreach ($counters as $key => $value) {
                $result .= $key . ':  ' . TimeHandle::timestampToDate($value) . "\n";
            }
        }
        return $result;
    }

    public static function getCountersInfoFilling(): string
    {
        $result = 'no data';
        $cottages = self::find()->all();
        $counters = [];
        if (!empty($cottages)) {
            /** @var Cottages $cottage */
            foreach ($cottages as $cottage) {
                $counters[$cottage->reader_id] = $cottage->last_indication_time;
            }
        }
        if (!empty($counters)) {
            asort($counters);
            $result = '';
            foreach ($counters as $key => $value) {
                $result .= $key . ':  ' . TimeHandle::timestampToDate($value) . "\n";
            }
        }
        return $result;
    }

}