<?php


namespace app\models\database;


use yii\db\ActiveRecord;

/**
 * @property int $id [int(10) unsigned]
 * @property string $raw_data [char(48)]
 * @property string $dev_eui [char(16)]
 * @property int $receiving_time [int(11)]
 */
class ReceivedData extends ActiveRecord
{



    public static function tableName(): string
    {
        return 'received_data';
    }

    public static function notRegistered(string $reader_id, $rawData): bool
    {
        return !self::find()->where(['dev_eui' => $reader_id, 'raw_data' => $rawData])->count();
    }

    public static function getLastData($deviceId): array
    {
        return self::find()->where(['dev_eui' => $deviceId])->orderBy('receiving_time DESC')->limit(20)->all();
    }

}