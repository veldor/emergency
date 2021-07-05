<?php



/* @var $text string $ */


?>
<!DOCTYPE HTML>
<html lang="ru">
<head>
    <meta name='viewport'
          content='width=device-width, initial-scale=1.0, maximum-scale=1.0,
     user-scalable=0' >
    <meta charset="utf-8">
    <style type="text/css">
        #main-table {
            font-family: Arial, Times New Roman, Helvetica, sans-serif;
            max-width: 600px;
            width: 100%;
            margin: auto;
            padding: 0;
            border: 20px solid #CFE7E7;
            border-radius: 10px;
            border-spacing: 0;
        }

        .advice-table{
            font-family: Arial, Times New Roman, Helvetica, sans-serif;
            max-width: 600px;
            width: 100%;
            margin: auto;
            padding: 0;
            border-radius: 10px;
            border-spacing: 0;
        }

        .text-center {
            text-align: center;
        }

        a {
            color: #3cadde;
        }

        a.btn{
            text-decoration: none;
        }

        * .btn {
            display: inline-block;
            margin-bottom: 5px;
            font-weight: normal;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            -ms-touch-action: manipulation;
            touch-action: manipulation;
            cursor: pointer;
            background-image: none;
            border: 1px solid transparent;
            padding: 6px 12px;
            font-size: 14px;
            line-height: 1.42857143;
            border-radius: 4px;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            color: #fff !important;
        }

        *.btn-primary {
            background-color: #337ab7;
            border-color: #2e6da4;
        }

        *.btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }

        *.btn-info {
            background-color: #5bc0de;
            border-color: #46b8da;
        }

        td.filled{
            padding: 15px 20px;
            background-color: #CFE7E7;
            text-align: left;
        }

        tr td.overline{
            border-top: 5px solid #CFE7E7;
        }
        div.btns-block, .fit-down{
            margin-bottom: 1em;
        }

        img.advice{
            width: 100%;
        }

        .notice{
            text-decoration: underline;
        }

        .fit-down{
            margin-top: 1em;
        }

    </style>
    <title></title>
</head>
<body class="text-center">
<table id="main-table" class="text-center">
    <tbody>
    <tr>
        <td colspan="2" class="text-center">
            <a href="http://xn----ttbeqkc.xn--p1ai/"><img alt="logo image" class="advice" src="https://rdcnn.ru/images/horizontal-logo.png"/></a>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <?= $text ?>
        </td>
    </tr>

    <tr>
        <td class="overline" colspan="2">
            <!--Футер-->
            <h3 class="text-center">У вас есть вопросы?</h3>
            <p class="text-center"><a href="tel:88312020200">Кликните, чтобы позвонить нам<br/>+7(831)20-20-200</a></p>
            <p class="text-center"><a href="mailto:clinica@rdcnn.ru">Кликните, чтобы написать нам</a></p>
        </td>
    </tr>
    <tr>
        <td colspan="2" class="filled">
            <b>С заботой о Вас и Ваших близких!</b><br/>
            <b>Региональный Диагностический Центр</b><br/>
            <a href="http://xn----ttbeqkc.xn--p1ai/">мрт-кт.рф</a><br/>
            <a href="tel:88312020200">+7(831)20-20-200</a>
        </td>
    </tr>
    </tbody>
</table>
<table class="text-center advice-table">
    <tr>
        <td colspan="2">
            <div class="advice">
                <h3>Актуальные предложения</h3>
                <a href="http://xn----ttbeqkc.xn--p1ai/nn/actions">
                    <img class="advice" alt="advice image" src="http://xn----ttbeqkc.xn--p1ai/actions.png"/>
                </a>
            </div>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <a href='https://rdcnn.ru/unsubscribe/{patient_unsubscribe_token}'><b>Если не хотите получать от нас письма- нажмите сюда</b></a>
        </td>
    </tr>
</table>
</body>
</html>




