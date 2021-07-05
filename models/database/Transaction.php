<?php


namespace app\models\database;


use app\models\User;
use yii\db\ActiveRecord;

/**
 * @property int $id [int(10) unsigned]  Глобальный идентификатор
 * @property int $cottageNumber [int(10) unsigned]
 * @property int $billId [int(10) unsigned]
 * @property int $transactionDate [int(10) unsigned]
 * @property string $transactionType [enum('cash', 'no-cash')]
 * @property string $transactionSumm [double unsigned]
 * @property string $transactionWay [enum('in', 'out')]
 * @property string $transactionReason
 * @property string $billCast
 * @property float $usedDeposit [double]
 * @property float $gainedDeposit [double]
 * @property bool $partial [tinyint(1)]
 * @property int $payDate [int(10) unsigned]
 * @property int $bankDate [int(10) unsigned]
 */
class Transaction extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'transactions';
    }

    public static function get($id): Transaction
    {
        $existent = self::findOne($id);
        return $existent ?? new Transaction();
    }
}