<?php

namespace app\models;


class PayedItem
{
    public int $id;
    public string $sum;
    public string $billId;
    public int $transactionId;
    public int $paymentDate;
}