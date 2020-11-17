<?php


namespace app\models\database;


use yii\db\ActiveRecord;

/**
 * @property string $client_id [varchar(255)]  идентификатор пользователя
 * @property string $client_cottage_number [varchar(10)]  номер привязанного участка
 * @property int $access_level [int(1) unsigned]  уровень доступа
 */

class Telegram_clients extends ActiveRecord
{
   private const LEVEL_USER = 1;

    public static function tableName(): string
    {
        return 'telegram_clients';
    }

    public static function isCottageSigned(int $id): bool
    {
        /** @var Telegram_clients $person */
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

    public static function get(int $clientId)
    {
        return self::findOne(['client_id' => $clientId]);
    }

    /**
     * @param Cottages $cottage
     * @return Telegram_clients[]
     */
    public static function getSubscribers(Cottages $cottage)
    {
        return self::findAll(['client_cottage_number' => $cottage->cottage_number]);
    }

    /**
     * @return Telegram_clients[]
     */
    public static function getAll()
    {
        return self::find()->all();
    }
}