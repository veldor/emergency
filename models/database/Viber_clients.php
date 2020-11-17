<?php


namespace app\models\database;


use yii\db\ActiveRecord;

/**
 * @property string $client_id [varchar(255)]  идентификатор пользователя
 * @property int $id [int(10) unsigned]
 * @property string $client_cottage_number [varchar(10)]
 * @property int $access_level [int(1) unsigned]
 */

class Viber_clients extends ActiveRecord
{
   private const LEVEL_USER = 1;

    public static function tableName(): string
    {
        return 'viber_clients';
    }

    public static function isCottageSigned(int $id): bool
    {
        /** @var Viber_clients $person */
        $person = self::findOne(['client_id' => $id]);
        return ($person !== null && !empty($person->client_cottage_number));
    }

    public static function bind(string $chatId, string $cottageNumber): void
    {
        $existent = self::findOne(['client_id' => $chatId, 'client_cottage_number' => $cottageNumber]);
        if($existent === null){
            (new self(['client_cottage_number' => $cottageNumber, 'client_id' => $chatId, 'access_level' => self::LEVEL_USER]))->save();
        }
    }

    public static function get(string $receiverId)
    {
        return self::findOne(['client_id' => $receiverId]);
    }

    public static function getAll()
    {
        return self::find()->all();
    }
}