<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 18.04.2019
 * Time: 9:42
 */

use app\models\utils\QrHandler;
use yii\web\View;

/* @var $this View */
/* @var $info QrHandler */



$qr = $info->drawQR();


$text = "
<div class='description margened'><span>ПАО СБЕРБАНК</span><span class='pull-right''>Форма №ПД-4</span></div>

<div class='text-center bottom-bordered'><b>$info->name</b></div>
<div class='text-center description margened'><span>(Наименование получателя платежа)</span></div>
<div class='bottom-bordered'><span><b>ИНН</b> $info->payerInn <b>КПП</b> $info->kpp</span><span class='pull-right'>$info->personalAcc</span></div>
<div class='description margened'><span>(инн получателя платежа)</span><span class='pull-right'>(номер счёта получателя платежа)</span></div>
<div class='bottom-bordered text-center'><span><b>БИК</b> $info->bik ($info->bankName)</span></div>
<div class='text-center description margened'><span>(Наименование банка получателя платежа)</span></div>
<div class='bottom-bordered text-underline'><b>Участок </b>№$info->cottageNumber ;<b> ФИО:</b> $info->lastName; <b>Назначение:</b> $info->purpose;</b></div>
<div class='description margened text-center'><span>(назначение платежа)</span></div>
<div class='text-center bottom-bordered'><b>Сумма: $info->summ</b></div>
<div class='description margened text-center'><span>(сумма платежа)</span></div>

<div class='description margened'><span>С условиями приёма указанной в платёжном документе суммы, в т.ч. с суммой взимаемой платы за услуги банка, ознакомлен и согласен. </span><br/><br/><span class='pull-right'>Подпись плательщика <span class='sign-span bottom-bordered'></span></span></div>
";
?>
<!DOCTYPE HTML>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Квитанции</title>
    <style type="text/css">
        div#invoiceWrapper {
            width: 180mm;
            margin: auto;
            font-size: 10px;
        }

        .margened {
            margin-bottom: 10px;
            margin-top: 5px;
        }

        .col-xs-12 {
            width: 100%;
        }

        td.leftSide {
            text-align: center;
            width: 65mm;
            border-right: 1px solid black;
        }

        img.qr-img {
            width: 80%;
        }

        .bottom-bordered {
            border-bottom: 1px solid black;
        }

        .description {
            font-size: 8px;
        }

        .text-underline {
        }

        .margened {
            margin-bottom: 10px;
        }

        .sign-span {
            width: 20mm;
            display: inline-block;
        }

        table > thead > tr > th, .table > tbody > tr > th, .table > tfoot > tr > th, .table > thead > tr > td, .table > tbody > tr > td, .table > tfoot > tr > td {
            padding: 8px;
            line-height: 1.42857143;
            vertical-align: top;
            border-top: 1px solid #ddd;
        }

        .pull-right {
            float: right !important;
        }

        .text-center {
            text-align: center;
        }

        img.logo-img {
            width: 50%;
            margin-left: 25%;
        }

        p {
            line-height: 2;
        }
    </style>
</head>
<body>
<div id="invoiceWrapper">
    <img class="logo-img" src="<?php echo $_SERVER["DOCUMENT_ROOT"] . '/graphics/logo.png'; ?>" alt="logo">
    <table class="table">
        <tr>
            <td class="leftSide">
                <h3>Извещение</h3>
            </td>
            <td class="rightSide">
                <?= $text ?>
            </td>
        </tr>
        <tr>
            <td class="leftSide">
                <h3>Квитанция</h3>
                <img class="qr-img" src="<?= $qr ?>" alt=""/>
            </td>
            <td class="rightSide">
                <?= $text ?>
            </td>
        </tr>
    </table>
    </div>
</body>
</html>

