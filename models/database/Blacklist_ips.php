<?php


namespace app\models\database;



/**
 * @property int $errors_count [int(10) unsigned]  количество неудачных попыток
 * @property int $last_try_time [bigint(20) unsigned]  время последней попытки входа
 * @property bool $is_blocked [tinyint(1)]  маркер блокировки
 * @property int $global_id [int(10) unsigned]
 * @property string $ip [varchar(255)]  ip неудачника
 */

class Blacklist_ips extends Blacklist_item_base
{
    private const WRONG_TRY_TO_BLOCK = 10;
    private const WRONG_TRY_TO_WARNING = 5;
    private const WAITING_PERIOD = 300;

    public static function tableName(): string
    {
        return 'blacklist_ip';
    }

    public static function registerWrongTry(): void
    {
        $ip = self::getIp();
        $existent = self::findOne(['ip' => $ip]);
        if($existent === null){
            $existent = new self();
            $existent->ip = $ip;
            $existent->last_try_time = time();
            $existent->errors_count = 1;
            $existent->save();
        }
        else{
            $existent->updateCounters(['errors_count' => 1]);
            if($existent->errors_count === self::WRONG_TRY_TO_BLOCK){
                self::blockUser();
            }
        }
    }

    private static function getIp(){
        return $_SERVER['REMOTE_ADDR'];
}

    public static function isLoginError()
    {
        $existent = self::findOne(['ip' => self::getIp()]);
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

    public static function blockUser(): void
    {
        $existent = self::findOne(['ip' =>  self::getIp()]);
        if($existent !== null && $existent->errors_count === self::WRONG_TRY_TO_BLOCK){
            $existent->is_blocked = 1;
            $existent->save();
        }
    }

    public static function clearTry(): void
    {
        $existent = self::findOne(['ip' => self::getIp()]);
        if($existent !== null){
            $existent->errors_count = 0;
            $existent->save();
        }
    }
}