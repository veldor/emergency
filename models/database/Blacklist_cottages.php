<?php


namespace app\models\database;


use app\models\User;

/**
 * @property int $errors_count [int(10) unsigned]  количество неудачных попыток
 * @property int $last_try_time [bigint(20) unsigned]  время последней попытки входа
 * @property bool $is_blocked [tinyint(1)]  маркер блокировки
 * @property int $id [int(10) unsigned]
 * @property string $cottage_id [varchar(10)]  номер участка
 */

class Blacklist_cottages extends Blacklist_item_base
{
    private const WRONG_TRY_TO_BLOCK = 15;
    private const WRONG_TRY_TO_WARNING = 10;
    private const WAITING_PERIOD = 300;

    public static function tableName(): string
    {
        return 'blacklist_cottage';
    }

    public static function registerWrongTry(string $username): void
    {
        // проверю наличие участка с данным именем
        if(User::findByUsername($username) !== null){
            $existent = self::findOne(['cottage_id' => $username]);
            if($existent === null){
                $existent = new self();
                $existent->cottage_id = $username;
                $existent->last_try_time = time();
                $existent->errors_count = 1;
                $existent->save();
            }
            else{
                $existent->updateCounters(['errors_count' => 1]);
                if($existent->errors_count === self::WRONG_TRY_TO_BLOCK){
                    self::blockUser($username);
                }
            }
        }
    }

    /**
     * @param string $username
     * @return string|null
     */
    public static function isLoginError(string $username): ?string
    {
        $existent = self::findOne(['cottage_id' => $username]);
         if($existent !== null){
             if($existent->is_blocked){
                 return 'Учётная запись пользователя заблокирована. Обратитесь к администратору для разблокировки';
             }
             if($existent->errors_count >= self::WRONG_TRY_TO_WARNING && time() - self::WAITING_PERIOD < $existent->last_try_time){
                 return 'Слишком много неудачных попыток входа. Попробуйте ещё раз через несколько минут';
             }
         }
         return null;
    }

    private static function blockUser(string $username): void
    {
        $existent = self::findOne(['cottage_id' => $username]);
        if($existent !== null && $existent->errors_count === self::WRONG_TRY_TO_BLOCK){
            $user = User::findByUsername($username);
            if($user !== null){
                $user->status = 2;
                $user->save();
            }
            $existent->is_blocked = 1;
            $existent->save();
        }
    }

    public static function clearTry(string $cottage_number): void
    {
        $existent = self::findOne(['cottage_id' => $cottage_number]);
        if($existent !== null){
            $existent->errors_count = 0;
            $existent->save();
        }
    }
}