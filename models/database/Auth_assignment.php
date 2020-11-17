<?php


namespace app\models\database;



/**
 * @property string $item_name [varchar(64)]
 * @property string $user_id [varchar(64)]
 * @property int $created_at [int(11)]
 */

class Auth_assignment extends Blacklist_item_base
{

    public static function tableName(): string
    {
        return 'auth_assignment';
    }
}