<?php


namespace app\models\utils;


use app\models\exceptions\InvalidParamException;

class RawDataUtils
{

    /**
     * Получение типа данных
     * @param $rawData
     * @return string
     */
    public function getPacketType($rawData): string
    {
        return mb_substr($rawData, 0, 2);
    }

    public function checkBatteryLevel($rawData)
    {
        return $this->intFromHex(mb_substr($rawData, 2, 2));
    }

    /**
     * Простой перевод шестнадцатиричного значения в десятиричное
     * @param $hex
     * @return int
     */
    public function intFromHex($hex):int
    {
        return hexdec($hex);
    }

    /**
     * Возвращает байтовое поле настроек
     * @param $rawData
     * @return string
     */
    public function getByteSettings($rawData): string
    {
        $converted =  $this->strToBin(mb_substr($rawData, 4, 2));
        return strrev(str_pad($converted, 8, "0", STR_PAD_LEFT));
    }

    /**
     * Возвращает байтовое содержимое строки
     * @param $str
     * @return string
     */
    public function strToBin($str): string
    {
        return base_convert($str, 16, 2);
    }

    /**
     * Чтение типа активации из строки битовых настроек
     * @param string $byteSettings
     * @return string <b>returns OTAA or ABP value</b>
     */
    public function getActivationType(string $byteSettings): string
    {
        if (mb_strpos($byteSettings, "0") === 0) {
            return "OTAA";
        }
        return "ABP";
    }

    /**
     * Получение значения интервала выхода на связь
     * @param string $byteSettings
     * @return string <b>return time in string value</b>
     * @throws InvalidParamException
     */
    public function getPingInterval(string $byteSettings): string
    {
        $value = mb_substr($byteSettings, 1, 3);
        switch ($value) {
            case '000':
                return '5 минут';
            case '100':
                return '15 минут';
            case '010':
                return '30 минут';
            case '110':
                return '1 час';
            case '001':
                return '6 часов';
            case '101':
                return '12 часов';
            case '011':
                return '24 часа';
        }
        throw new InvalidParamException("Неверное значение битового поля");
    }

    public function getTime($rawData): int
    {
        $rawOperationType = $this->revertHex(substr($rawData, 6, 8));
        return $this->intFromHex($rawOperationType);
    }
    public function revertHex(string $s): string
    {
        return $s[6] . $s[7] . $s[4] . $s[5] . $s[2] . $s[3] . $s[0] . $s[1];
    }

    public function getExternalTemperature($rawData)
    {
        $rawValue = substr($rawData, 14,2);
        $temp = intval($rawValue, 16);
        if($temp < 127)
            return "+" . $temp;
        return $temp - 256;
    }

    /**
     * Возвращает тип входа на основе бинарных данных
     * @param string $byteData
     * @param int $int
     * @return string
     */
    public function getPinType(string $byteData, int $int): string
    {
        $type = $byteData[3 + $int];
        if((bool)$type){
            return "охранный";
        }
        return "импульсный";
    }

    public function getPinValue($rawData,$pinNumber, string $pinType): string
    {
        $rawValue = $this->revertHex(substr($rawData, 8 + ($pinNumber * 8), 8));

        if($pinType === "охранный"){
            if((int) $rawValue){return "замкнут";}
            return "разомкнут";
        }
        return round($this->intFromHex($rawValue) / 3200, 3);
    }

    public function getAlertPin($rawData): int
    {
        return((int)substr($rawData, 6, 2));
    }

    public function getAlertTime($rawData): int
    {
        $rawOperationType = $this->revertHex(substr($rawData, 8, 8));
        return $this->intFromHex($rawOperationType);
    }
}