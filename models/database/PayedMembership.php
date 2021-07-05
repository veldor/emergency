<?php


namespace app\models\database;


use yii\db\ActiveRecord;

/**
 * @property int $id [int(10) unsigned]  Глобальный идентификатор
 * @property int $cottageId [int(10) unsigned]
 * @property int $billId [int(10) unsigned]
 * @property string $quarter [varchar(10)]
 * @property string $summ [float unsigned]
 * @property int $paymentDate [int(10) unsigned]
 * @property int $transactionId [int(10) unsigned]
 */

class PayedMembership extends ActiveRecord
{

    public static function tableName(): string
    {
        return 'payed_membership';
    }

    public static function get($id): PayedMembership
    {
        $existent = self::findOne($id);
        return $existent ?? new PayedMembership();
    }

    public static function getPayed(string $cottage_number, string $quarter): int
    {
        $payed = 0;
        $pays = self::findAll(['quarter' => $quarter, 'cottageId' => $cottage_number]);
        if(!empty($pays)){
            foreach ($pays as $pay) {
                $payed += ($pay->summ * 100);
            }
        }
        return $payed;
    }
}