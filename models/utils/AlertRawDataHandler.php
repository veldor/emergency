<?php


namespace app\models\utils;


class AlertRawDataHandler
{
    private string $rawData;

    public function __construct($rawData)
    {
        $this->rawData = $rawData;
    }

    public function decodeRawData()
    {

    }

    public function revertHex(string $s): string
    {
        return $s[6] . $s[7] . $s[4] . $s[5] . $s[2] . $s[3] . $s[0] . $s[1];
    }

    public function timeFromHex(string $hex)
    {
        $timestamp = $this->intFromHex($hex);

    }

    public function intFromHex($hex)
    {
        return hexdec($hex);
    }

    public function getOperationType(): int
    {
        $rawOperationType = mb_substr($this->rawData, 0, 2);
        return (int) $rawOperationType;
    }

    public function getBatteryChargeLevel()
    {
        $rawOperationType = mb_substr($this->rawData, 2, 2);
        return $this->intFromHex($rawOperationType);
    }

    public function strToBin($str){
        $value = unpack('H*', $str);
        return base_convert($value[1], 16, 2);
    }

    public function getCounterSettings()
    {
        return $this->strToBin(mb_substr($this->rawData, 4, 2));
    }

    public function getActivePin()
    {
        $rawOperationType = mb_substr($this->rawData, 6, 2);
        return (int) $rawOperationType;
    }

    public function getAlertTime(): string
    {
        $rawOperationType = $this->revertHex(mb_substr($this->rawData, 8, 8)) ;
        return TimeHandle::timestampToDate($this->intFromHex($rawOperationType));
    }

    public function getPinStatus(int $activePin)
    {
        $rawValue = $this->revertHex(mb_substr($this->rawData, 8 + (8 * $activePin), 8)) ;
        return (int) $rawValue === 1 ? "замкнут" : "разомкнут";
    }
}