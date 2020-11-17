<?php


namespace app\models\database;


use app\models\User;
use phpDocumentor\Reflection\Types\Null_;
use Yii;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;

/**
 * @property int $id [int(10) unsigned]  Глобальный идентификатор
 * @property int $device [int(10) unsigned]  Идентификатор устройства
 * @property int $alert_start_time [bigint(20) unsigned]  Время поступления сигнала
 * @property bool $is_confirmed [tinyint(1)]  Сигнал принят пользователем
 * @property bool $is_stopped [tinyint(1)]  Тревога остановлена
 * @property string $raw_data [char(48)]
 */

class Alert extends ActiveRecord
{


    public static function tableName(): string
    {
        return 'alerts';
    }

    /**
     * @param DefenceDevice $defenceDevice
     * @return Alert[]|null
     */
    public static function findUnhandled(DefenceDevice $defenceDevice): array
    {
        return self::findAll(['device' => $defenceDevice->id, 'is_confirmed' => 0]);
    }

    /**
     * Отмечу все тревоги по данному устройству обработанными
     * @param DefenceDevice $deviceInfo
     * @return bool
     */
    public static function setConfirmed(DefenceDevice $deviceInfo):bool
    {
        // отмечу отработанными все уведомления
        $existentAlerts = self::findAll(['is_confirmed' => 0, 'device' => $deviceInfo->id]);
        if(!empty($existentAlerts)){
            foreach ($existentAlerts as $existentAlert) {
                $existentAlert->is_confirmed = 1;
                $existentAlert->save();
            }
        }
        return true;
    }

}