<?php


namespace app\models\database;


use app\models\User;
use phpDocumentor\Reflection\Types\Null_;
use Yii;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;

/**
 * @property int $id [int(10) unsigned]  Глобальный идентификатор
 * @property int $device [int(10) unsigned]  Целевое устройство
 * @property int $requested_state [int(1) unsigned]  Запрошенное состояние
 * @property bool $is_accepted [tinyint(1)]  Запрос подтверждён
 */

class DefenceStatusChangeRequest extends ActiveRecord
{


    public static function tableName(): string
    {
        return 'defence_status_change_requests';
    }

    public static function waitForConfirm(DefenceDevice $defenceDevice): bool
    {
        return self::find()->where(['device' => $defenceDevice->id, 'is_accepted' => 0])->count() !== '0';
    }

    public static function createNew(DefenceDevice $defenceDevice, $mode): void
    {
        (new self(['device' => $defenceDevice->id, 'requested_state' => $mode]))->save();
    }

    /**
     * @return DefenceStatusChangeRequest[]|null
     */
    public static function getWaitingForConfirm(): ?array
    {
        return self::find()->where(['is_accepted' => 0])->all();
    }

}