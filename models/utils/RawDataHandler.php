<?php


namespace app\models\utils;


use app\models\exceptions\InvalidParamException;

class RawDataHandler
{
    public string $rawData;
    public int $batteryLevel;
    public int $packetType;
    public string $activationType;
    public string $pingInterval;
    public string $indicationTime;
    public string $externalTemperature;
    public string $pin_1_type;
    public string $pin_2_type;
    public string $pin_3_type;
    public string $pin_4_type;
    public string $pin_1_value;
    public string $pin_2_value;
    public string $pin_3_value;
    public string $pin_4_value;
    public int $alert_pin;

    /**
     * RawDataHandler constructor.
     * @param $rawData
     * @throws InvalidParamException
     */
    public function __construct($rawData){
        // проверю, если полученное значение не строка 48 символов длиной- класс используется ошибочно
        if(empty($rawData) || strlen($rawData) !== 48){
            throw new InvalidParamException("Значение не является rawData");
        }
        $this->rawData = $rawData;
        $utils = new RawDataUtils();
        // разберу значение
        // получу тип пакета
        $type = $utils->getPacketType($rawData);
        switch ($type){
            case "01":
                // Пакет с текущими показаниями
                $this->packetType = 1;
                $this->indicationTime = $utils->getTime($rawData);
                $this->externalTemperature = $utils->getExternalTemperature($rawData);
                // а для интерпретации показаний нужно проверить, являются ли контакты импульсными счётчиками
                $this->fillBaseData($utils, $rawData);
                break;
            case "02":
                // Пакет с текущими показаниями
                $this->alert_pin = $utils->getAlertPin($rawData);
                $this->indicationTime = $utils->getAlertTime($rawData);
                $this->packetType = 2;
                $this->fillBaseData($utils, $rawData);
                break;
        }
    }

    /**
     * @param RawDataUtils $utils
     * @param $rawData
     * @throws InvalidParamException
     */
    private function fillBaseData(RawDataUtils $utils, $rawData): void
    {
        $this->batteryLevel = $utils->checkBatteryLevel($rawData);
        // а для интерпретации показаний нужно проверить, являются ли контакты импульсными счётчиками
        $byteData = $utils->getByteSettings($rawData);
        $this->activationType = $utils->getActivationType($byteData);
        $this->indicationTime = $utils->getPingInterval($byteData);
        $this->pin_1_type = $utils->getPinType($byteData, 1);
        $this->pin_2_type = $utils->getPinType($byteData, 2);
        $this->pin_3_type = $utils->getPinType($byteData, 3);
        $this->pin_4_type = $utils->getPinType($byteData, 4);
        $this->pin_1_value = $utils->getPinValue($rawData, 1, $this->pin_1_type);
        $this->pin_2_value = $utils->getPinValue($rawData, 2, $this->pin_2_type);
        $this->pin_3_value = $utils->getPinValue($rawData, 3, $this->pin_3_type);
        $this->pin_4_value = $utils->getPinValue($rawData, 4, $this->pin_4_type);
    }
}