<?php


namespace app\models\database;


use app\models\User;
use yii\db\ActiveRecord;

/**
 * @property int $id [int(10) unsigned]  Глобальный идентификатор
 * @property int $cottageNumber [int(10) unsigned]
 * @property string $month [varchar(20)]
 * @property int $fillingDate [int(10) unsigned]
 * @property int $oldPowerData [int(10) unsigned]
 * @property int $newPowerData [int(10) unsigned]
 * @property int $searchTimestamp [int(10) unsigned]
 * @property string $payed [enum('yes', 'no')]
 * @property int $difference [int(10) unsigned]
 * @property string $totalPay [float unsigned]
 * @property int $inLimitSumm [int(10) unsigned]
 * @property int $overLimitSumm [int(10) unsigned]
 * @property string $inLimitPay [float unsigned]
 * @property string $overLimitPay [float unsigned]
 * @property string $payedYet [float unsigned]
 */
class AccrualsPower extends ActiveRecord
{

    /**
     * @var int
     */
    public $payedYet = 0;

    public static function tableName(): string
    {
        return 'accruals_power';
    }

    public static function get($id): AccrualsPower
    {
        $existent = self::findOne($id);
        return $existent ?? new AccrualsPower();
    }

    public static function getBalance(User $user)
    {
        $balance = 0;
        $data = self::find()->where(['cottageNumber' => $user->cottage_number])->all();
        if (!empty($data)) {
            foreach ($data as $item) {
                $accrued = ($item->totalPay * 100);
                // get payed sum
                $payed = PayedPower::getPayed($item->cottageNumber, $item->month);
                $balance += $accrued - $payed;
            }
        }
        return $balance;
    }

    public static function getSlice(?User $user, $limit, $offset)
    {
        if ($user !== null) {
            $data = self::find()->where(['cottageNumber' => $user->cottage_number])->orderBy('month DESC')->limit($limit)->offset($offset)->all();
            if(!empty($data)){
                /** @var AccrualsPower $item */
                foreach ($data as $item) {
                    $item->payed = PayedPower::getPayed($user->cottage_number, $item->month);
                    $item->totalPay = (int)($item->totalPay * 100);
                }
            }
            return ['status' => 'success', 'list' => $data, 'count' => self::find()->where(['cottageNumber' => $user->cottage_number])->count()];
        }
        return [];
    }
}