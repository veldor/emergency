<?php


namespace app\models\utils;


use app\models\exceptions\InvalidParamException;

class PowerStateChangeDataHandler
{
    public string $rawData;
    public int $batteryLevel;
    public int $packetType;
    public string $activationType;
    public string $pingInterval;
    public string $indicationTime;
    public bool $powerState;

    /**
     * RawDataHandler constructor.
     * @param $rawData
     * @throws InvalidParamException
     */
    public function __construct($rawData)
    {
        // проверю, если полученное значение не строка 48 символов длиной- класс используется ошибочно
        if (empty($rawData) || strlen($rawData) !== 16) {
            throw new InvalidParamException("Значение не является rawData");
        }
        $this->rawData = $rawData;
        $utils = new RawDataUtils();
        // разберу значение
        $this->packetType = 3;
        $this->indicationTime = $utils->getAlertTime($rawData);
        $this->fillBaseData($utils, $rawData);
        $this->powerState = $this->getPowerState();
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
        $this->pingInterval = $utils->getPingInterval($byteData);
    }

    public function getPowerState()
    {
        $rawOperationType = mb_substr($this->rawData, 6, 2);
        return (bool) $rawOperationType;
    }
}