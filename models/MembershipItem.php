<?php


namespace app\models;


class MembershipItem
{
    public int $id;
    public string $cottage_number;
    public string $quarter;
    public $fixed_part;
    public $square_part;
    public int $counted_square;
    public float $payed;
}