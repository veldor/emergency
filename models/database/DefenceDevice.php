<?php


namespace app\models\database;


use app\models\User;
use phpDocumentor\Reflection\Types\Null_;
use Yii;
use yii\db\ActiveRecord;
use yii\db\StaleObjectException;

/**
 * @property int $id [int(10) unsigned]  Глобальный идентификатор
 * @property int $devEui [int(16) unsigned]  Идентификатор считывателя
 * @property int $port [int(1) unsigned]  Порт считывателя
 * @property bool $status [tinyint(1)]  Статус защиты
 */
class DefenceDevice extends ActiveRecord
{


    public static function tableName(): string
    {
        return 'defence_devices';
    }

    public static function getCottageDevice(Cottages $boundCottage)
    {
        if($boundCottage->binded_defence_device !== null){
            return self::findOne(['id' => $boundCottage->binded_defence_device]);
        }
        return null;
    }

}