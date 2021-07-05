<?php


namespace app\models\database;


use app\models\BillEntity;
use app\models\PayedItem;
use app\models\utils\DOMHandler;
use app\models\User;
use app\models\utils\FirebaseHandler;
use app\models\utils\GrammarHandler;
use app\models\utils\QrHandler;
use yii\db\ActiveRecord;

/**
 * @property int $id [int(10) unsigned]  Глобальный идентификатор
 * @property int $cottageNumber [int(10) unsigned]
 * @property string $bill_content
 * @property bool $isPayed [tinyint(1)]
 * @property int $creationTime [int(10) unsigned]
 * @property int $paymentTime [int(10) unsigned]
 * @property string $depositUsed [double unsigned]
 * @property float $totalSumm [double]
 * @property string $payedSumm [double unsigned]
 * @property string $discount [double unsigned]
 * @property string $discountReason
 * @property string $toDeposit [double unsigned]
 * @property bool $isPartialPayed [tinyint(4)]
 * @property bool $isMessageSend [tinyint(1)]  Уведомление отправлено
 * @property bool $isInvoicePrinted [tinyint(1)]  Квитанция распечатана
 * @property string $payer_personals [varchar(255)]  Р?РјСЏ РїР»Р°С‚РµР»СЊС‰РёРєР°
 */
class Bill extends ActiveRecord
{
    public static function tableName(): string
    {
        return 'payment_bills';
    }

    public static function get($id): Bill
    {
        $existent = self::findOne($id);
        return $existent ?? new Bill();
    }

    public static function getState(?User $user): array
    {
        if($user !== null){
            $unpayedBillsCount = self::find()->where(['cottageNumber' => $user->cottage_number, 'isPayed' => 0])->count();
            $payedBillsCount = self::find()->where(['cottageNumber' => $user->cottage_number, 'isPayed' => 1])->andWhere(['>','payedSumm', 0])->count();
            return ['status' => 'success', 'payed' => $payedBillsCount, 'unpayed' => $unpayedBillsCount];
        }
        return [];
    }

    /**
     * @param User|null $user
     * @param $limit
     * @param $offset
     * @param $isPayed
     * @return array
     */
    public static function getSlice(?User $user, $limit, $offset, $isPayed): array
    {
        if($user !== null){
            if($isPayed){
                $count = self::find()->where(['cottageNumber' => $user->cottage_number, 'isPayed' => 1])->andWhere(['>','payedSumm', 0])->count();
                $data = self::find()->where(['cottageNumber' => $user->cottage_number, 'isPayed' => 1])->andWhere(['>','payedSumm', 0])->orderBy('creationTime DESC')->limit($limit)->offset($offset)->all();
            }
            else{
                $count = self::find()->where(['cottageNumber' => $user->cottage_number, 'isPayed' => 0])->count();
                $data = self::find()->where(['cottageNumber' => $user->cottage_number, 'isPayed' => 0])->orderBy('creationTime DESC')->limit($limit)->offset($offset)->all();
            }
            if(!empty($data)){
                /** @var Bill $item */
                foreach ($data as $item) {
                    $item->totalSumm = (int)($item->totalSumm * 100);
                    // посчитаю оплаты по данному счёту
                    $payed = 0;
                    $transactions = Transaction::findAll(['billId' => $item->id]);
                    if(!empty($transactions)){
                        foreach ($transactions as $transaction) {
                            $payed += $transaction->transactionSumm * 100;
                        }
                    }
                    $item->payedSumm = (int)$payed;
                }
            }
            return ['status' => 'success', 'list' => $data, 'count' => $count];

        }
        return [];
    }

    /**
     * @throws \Exception
     */
    public static function getBillInfo(?User $user, $billId): array
    {
        if($user !== null){
            $entities = [];
            $billInfo = self::findOne(['id' => $billId, 'cottageNumber' => $user->cottage_number]);
            if($billInfo !== null){
                // разберу сущности, входящие в счёт
                $dom = DOMHandler::getDom($billInfo->bill_content);
                $xpath = DOMHandler::getXpath($dom);
                // power
                $values = $xpath->query("//power/month");
                if($values->length > 0){
                    foreach ($values as $value) {
                        $attributes = DOMHandler::getElemAttributes($value);
                        $newEntity = new BillEntity();
                        $newEntity->type = "Электроэнергия";
                        $newEntity->period = $attributes['date'];
                        $newEntity->toPay = GrammarHandler::normalizeNumber($attributes['summ']);
                        $entities[] = $newEntity;
                    }
                }
                $values = $xpath->query("//membership/quarter");
                if($values->length > 0){
                    foreach ($values as $value) {
                        $attributes = DOMHandler::getElemAttributes($value);
                        $newEntity = new BillEntity();
                        $newEntity->type = "Членские взносы";
                        $newEntity->period = $attributes['date'];
                        $newEntity->toPay = GrammarHandler::normalizeNumber($attributes['summ']);
                        $entities[] = $newEntity;
                    }
                }
                $values = $xpath->query("//target/pay");
                if($values->length > 0){
                    foreach ($values as $value) {
                        $attributes = DOMHandler::getElemAttributes($value);
                        $newEntity = new BillEntity();
                        $newEntity->type = "Целевые взносы";
                        $newEntity->period = $attributes['year'];
                        $newEntity->toPay = GrammarHandler::normalizeNumber($attributes['summ']);
                        $entities[] = $newEntity;
                    }
                }
                // получу оплаты по этому счёту
                $transactions = Transaction::findAll(['billId' => $billId]);
                if(!empty($transactions)){
                    foreach ($transactions as $transaction) {
                        $transaction->transactionSumm = GrammarHandler::normalizeNumber($transaction->transactionSumm);
                    }
                }
                return ['status' => 'success', 'entities' => $entities, 'transactions' => $transactions];
            }
        }
        return [];
    }

    /**
     * @param User|null $user
     * @param $type
     * @param $period
     * @return array
     */
    public static function getPays(?User $user, $type, $period): array
    {
        if($user !== null){
            $pays = null;
            $result = [];
            switch ($type){
                case 'membership':
                    $pays = PayedMembership::findAll(['cottageId' => $user->cottage_number, 'quarter' => $period]);
                    break;
                case 'power':
                    $pays = PayedPower::findAll(['cottageId' => $user->cottage_number, 'month' => $period]);
                    break;
                case 'target':
                    $pays = PayedTarget::findAll(['cottageId' => $user->cottage_number, 'year' => $period]);
                    break;
            }
            if(!empty($pays)){
                foreach ($pays as $pay) {
                    $paymentItem = new PayedItem();
                    $paymentItem->billId = $pay->billId;
                    $paymentItem->transactionId = $pay->transactionId;
                    $paymentItem->paymentDate = $pay->paymentDate;
                    $paymentItem->sum = $pay->summ * 100;
                    $result[] = $paymentItem;
                }
            }
            return ['status' => 'success', 'list' => $result];
        }
        return [];
    }

    public static function getQrCodeData(?User $user, $billId)
    {
        if($user !== null){
            $billInfo = self::findOne(['id' => $billId, 'cottageNumber' => $user->cottage_number]);
            if($billInfo !== null){
                $handler = new QrHandler();
                return ['status' => 'success', 'qr_data' => $handler->getQrData($billInfo)];
            }
        }
        return [];
    }

    public static function getInfo(?User $user, $billId): ?QrHandler
    {
        if($user !== null){
            $billInfo = self::findOne(['id' => $billId, 'cottageNumber' => $user->cottage_number]);
            if($billInfo !== null){
                $handler = new QrHandler();
                $handler->fill($billInfo);
                return $handler;
            }
        }
        return null;
    }
}