<?php


namespace app\models\database;


use yii\db\ActiveRecord;

/**
 * @property int $id [int(10) unsigned
 * @property string $cottage_number [varchar(20)]
 * @property string $token [varchar(255)]
 * @property bool $wait_in [tinyint(1)]
 * @property bool $wait_out [tinyint(1)]
 */

class FirebaseDeviceBinding extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'firebase_device_binding';
    }

    public static function registerLogin(string $token, string $cottage_number): void
    {
        // проверю наличие записи
        $existent = self::findOne(['token' => $token]);
        if($existent === null){
            $existent = new self(['token' => $token, 'cottage_number' => $cottage_number]);
        }
        $existent->wait_in = 1;
        $existent->save();
    }

    /**
     * @return FirebaseDeviceBinding[]
     */
    public static function getWaiting(): ?array
    {
        return self::find()->where(['wait_in' => 1])->orWhere(['wait_out' => 1])->all();
    }

    public static function setTokenHandled($token): void
    {
        $existent = self::findOne(['token' => $token]);
        if($existent !== null){
            $existent->wait_in = 0;
            $existent->wait_out = 0;
            $existent->save();
        }
    }

    public static function registerLogout($token, $cottage_number): void
    {
        // проверю наличие записи
        $existent = self::findOne(['token' => $token]);
        if($existent === null){
            $existent = new self(['token' => $token, 'cottage_number' => $cottage_number]);
        }
        $existent->wait_out = 1;
        $existent->save();
    }
}