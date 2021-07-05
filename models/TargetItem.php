<?php


namespace app\models;


class TargetItem
{
    public int $id;
    public string $cottage_number;
    public string $quarter;
    public float $fixed_part;
    public float $square_part;
    public int $counted_square;
    public float $payed;
}