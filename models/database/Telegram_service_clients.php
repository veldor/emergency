<?php


namespace app\models\database;


use yii\db\ActiveRecord;

/**
 * @property string $client_id [varchar(255)]  идентификатор пользователя
 * @property int $id [int(10) unsigned]  Глобальный идентификатор
 */

class Telegram_service_clients extends ActiveRecord
{

    public static function tableName(): string
    {
        return 'telegram_service_registered_clients';
    }

    public static function isRegistered($id): bool
    {
        return (bool) self::find()->where(['client_id' => $id])->count();
    }
}