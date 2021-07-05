<?php


namespace app\models\utils;


use app\models\database\Bill;
use app\priv\Info;
use chillerlan\QRCode\QRCode;

class QrHandler
{
    private string $st = 'ST00012';
    public string $name = Info::BANK_INFO_NAME;
    public string $personalAcc = Info::BANK_INFO_PERSONAL_ACC;
    public string $bankName = Info::BANK_INFO_BANK_NAME;
    public string $bik = Info::BANK_INFO_BIK;
    private string $correspAcc = Info::BANK_INFO_CORRESP_ACC;
    public string $payerInn = Info::BANK_INFO_PAYER_INN;
    public string $kpp = Info::BANK_INFO_KPP;
    // personal info
    public string $purpose = '';
    public string $lastName = '';
    public string $summ = '';
    public string $cottageNumber = '';

    public function getQrData(Bill $billInfo): string
    {
        $purposeText = 'Оплата ';
        $dom = new DOMHandler($billInfo->bill_content);
        if (($dom->query('/power/month'))->length > 0) {
            $purposeText .= 'электроэнергии,';
        }
        if (($dom->query('/membership/quarter'))->length > 0) {
            $purposeText .= ' членских взносов,';
        }
        if (($dom->query('/target/pay'))->length > 0) {
            $purposeText .= ' целевых взносов,';
        }

        $this->purpose = substr($purposeText, 0, -1) . ' по сч. № ' . $billInfo->id;

        $this->lastName = $billInfo->payer_personals;
        $this->summ = $billInfo->totalSumm;
        $this->cottageNumber = $billInfo->cottageNumber;
        return "$this->st|Name=$this->name|PersonalAcc=$this->personalAcc|BankName=$this->bankName|BIC=$this->bik|CorrespAcc=$this->correspAcc|PayeeINN=$this->payerInn|KPP=$this->kpp|LASTNAME=$this->lastName|Purpose=$this->purpose|Sum=$this->summ|PersAcc={$this->cottageNumber}";

    }

    public function drawQR()
    {
        $data = "$this->st|Name=$this->name|PersonalAcc=$this->personalAcc|BankName=$this->bankName|BIC=$this->bik|CorrespAcc=$this->correspAcc|PayeeINN=$this->payerInn|KPP=$this->kpp|LASTNAME=$this->lastName|Purpose=$this->purpose|Sum=$this->summ|PersAcc={$this->cottageNumber}";
        return (new QRCode)->render($data);
    }

    public function fill(Bill $billInfo)
    {
        $purposeText = 'Оплата ';
        $dom = new DOMHandler($billInfo->bill_content);
        if (($dom->query('/power/month'))->length > 0) {
            $purposeText .= 'электроэнергии,';
        }
        if (($dom->query('/membership/quarter'))->length > 0) {
            $purposeText .= ' членских взносов,';
        }
        if (($dom->query('/target/pay'))->length > 0) {
            $purposeText .= ' целевых взносов,';
        }

        $this->purpose = substr($purposeText, 0, -1) . ' по сч. № ' . $billInfo->id;

        $this->lastName = $billInfo->payer_personals;
        $this->summ = $billInfo->totalSumm;
        $this->cottageNumber = $billInfo->cottageNumber;
    }
}