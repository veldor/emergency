<?php


namespace app\models\utils;


use app\models\database\AccrualsMembership;
use app\models\database\AccrualsPower;
use app\models\database\Bill;
use app\models\database\AccrualsTarget;
use app\models\User;

class AccrualsHandler
{

    public static function getStatus(?User $user): array
    {
        if($user === null){
            return [];
        }
        $membershipDuty = AccrualsMembership::getBalance($user);
        $powerDuty = (int)AccrualsPower::getBalance($user);
        $targetDuty = (int)AccrualsTarget::getBalance($user);
        $totalDuty = $membershipDuty + $powerDuty + $targetDuty;
        return [
            'status' => 'success',
            'totalDuty' => $totalDuty,
            'membershipDuty' => $membershipDuty,
            'powerDuty' => $powerDuty,
            'targetDuty' => $targetDuty,
        ];
    }
}