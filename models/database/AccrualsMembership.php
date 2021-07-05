<?php


namespace app\models\database;


use app\models\MembershipItem;
use app\models\User;
use app\models\utils\TimeHandler;
use yii\db\ActiveRecord;

/**
 * @property int $id [int(10) unsigned]  Глобальный идентификатор
 * @property string $cottage_number [varchar(10)]
 * @property string $quarter [varchar(56)]
 * @property float $fixed_part [float]
 * @property float $square_part [float]
 * @property int $counted_square [int(11)]
 */
class AccrualsMembership extends ActiveRecord
{

    public static function tableName(): string
    {
        return 'accruals_membership';
    }

    public static function get($id): AccrualsMembership
    {
        $existent = self::findOne($id);
        return $existent ?? new AccrualsMembership();
    }

    public static function getAccruals(User $user)
    {
        $accrued = 0;
        $data = self::findAll(['cottage_number' => $user->cottage_number]);
        if (!empty($data)) {
            foreach ($data as $item) {
                $accrued += (int)($item->fixed_part + ($item->square_part / 100 * $item->counted_square)) * 100;
            }
        }
        return $accrued;
    }

    public static function getBalance(User $user)
    {
        $balance = 0;
        $data = self::find()->where(['cottage_number' => $user->cottage_number])->andWhere(['<=', 'quarter', TimeHandler::getCurrentQuarter()])->all();
        if (!empty($data)) {
            foreach ($data as $item) {
                $accrued = (int)(($item->fixed_part + ($item->square_part / 100.0 * $item->counted_square)) * 100);
                // get payed sum
                $payed = (int)PayedMembership::getPayed($item->cottage_number, $item->quarter);
                $balance += $accrued - $payed;
            }
        }
        return $balance;
    }

    public static function getSlice(?User $user, $limit, $offset)
    {
        if ($user !== null) {
            $result = [];
            $data = self::find()->where(['cottage_number' => $user->cottage_number])->orderBy('quarter DESC')->limit($limit)->offset($offset)->all();
            if (!empty($data)) {
                /** @var AccrualsMembership $item */
                foreach ($data as $item) {
                    $newItem = new MembershipItem();
                    foreach ($item->attributes as $key => $value) {
                        $newItem->$key = $value;
                    }
                    $newItem->fixed_part = (int)$newItem->fixed_part * 100;
                    $newItem->square_part = (int)$newItem->square_part * 100;
                    $newItem->payed = PayedMembership::getPayed($user->cottage_number, $item->quarter);
                    $result[] = $newItem;
                }
            }
            return ['status' => 'success', 'list' => $result, 'count' => self::find()->where(['cottage_number' => $user->cottage_number])->count()];
        }
        return [];
    }
}