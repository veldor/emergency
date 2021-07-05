<?php


namespace app\models\database;


use yii\db\ActiveRecord;

/**
 * @property int $id [int(10) unsigned]  Глобальный идентификатор
 * @property int $billId [int(10) unsigned]
 * @property int $cottageId [int(10) unsigned]
 * @property string $month [varchar(10)]
 * @property string $summ [float unsigned]
 * @property int $paymentDate [int(10) unsigned]
 * @property int $transactionId [int(10) unsigned]
 */

class PayedPower extends ActiveRecord
{

    public static function tableName(): string
    {
        return 'payed_power';
    }
    public static function get($id): PayedPower
    {
        $existent = self::findOne($id);
        return $existent ?? new PayedPower();
    }

    public static function getPayed(string $cottage_number, string $month)
    {
        $payed = 0;
        $pays = self::findAll(['month' => $month, 'cottageId' => $cottage_number]);
        if(!empty($pays)){
            foreach ($pays as $pay) {
                $payed += $pay->summ * 100;
            }
        }
        return (int) $payed;
    }
}