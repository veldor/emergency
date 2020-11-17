<?php


namespace app\models\database;


/**
 * @property int $errors_count [int(10) unsigned]  количество неудачных попыток
 * @property int $last_try_time [bigint(20) unsigned]  время последней попытки входа
 * @property bool $is_blocked [tinyint(1)]  маркер блокировки
 * @property int $id [int(10) unsigned]
 * @property string $viber_id [varchar(255)]  идентификатор участника
 */

class Blacklist_viber extends Blacklist_item_base
{
    private const WRONG_TRY_TO_BLOCK = 10;
    private const WRONG_TRY_TO_WARNING = 5;
    private const WAITING_PERIOD = 300;

    public static function tableName(): string
    {
        return 'blacklist_viber';
    }

    public static function registerWrongTry($viberId): void
    {
        $existent = self::findOne(['viber_id' => $viberId]);
        if ($existent === null) {
            $existent = new self();
            $existent->viber_id = $viberId;
            $existent->last_try_time = time();
            $existent->errors_count = 1;
            $existent->save();
        } else {
            $existent->updateCounters(['errors_count' => 1]);
            if ($existent->errors_count === self::WRONG_TRY_TO_BLOCK) {
                self::blockUser($viberId);
            }
        }
    }

    public static function isLoginError($chatId)
    {
        $existent = self::findOne(['viber_id' => $chatId]);
        if ($existent !== null) {
            if ($existent->is_blocked) {
                return 'Учётная запись пользователя заблокирована. Обратитесь к администратору для разблокировки';
            }
            if ($existent->errors_count >= self::WRONG_TRY_TO_WARNING && time() - self::WAITING_PERIOD < $existent->last_try_time) {
                return 'Слишком много неудачных попыток входа. Попробуйте ещё раз через несколько минут';
            }
        }
        return null;
    }

    public static function blockUser($viberId): void
    {
        $existent = self::findOne(['viber_id' => $viberId]);
        if ($existent !== null && $existent->errors_count === self::WRONG_TRY_TO_BLOCK) {
            $existent->is_blocked = 1;
            $existent->save();
        }
    }

    public static function resetTry(string $viberId): void
    {
        $existentAccount = self::findOne(['viber_id' => $viberId]);
        if($existentAccount !== null){
            $existentAccount->errors_count = 0;
            $existentAccount->save();
        }
    }
}