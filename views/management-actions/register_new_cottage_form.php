<?php

use app\models\database\Cottages;
use yii\helpers\Html;
use yii\web\View as ViewAlias;
use yii\widgets\ActiveForm;


/* @var $this ViewAlias */
/* @var $matrix Cottages */

$form = ActiveForm::begin(['id' => 'registerNewCottageForm', 'options' => ['class' => 'form-horizontal bg-default no-print'], 'enableAjaxValidation' => false, 'validateOnSubmit' => false, 'validateOnChange' => false, 'validateOnBlur' => false, 'action' => ['/management-actions/register-cottage']]);
echo $form->field($matrix, 'cottage_number', ['template' =>
    '<div class="col-sm-4">{label}</div><div class="col-sm-8">{input}{error}{hint}</div>'])
    ->textInput();
echo $form->field($matrix, 'owner_personals', ['template' =>
    '<div class="col-sm-4">{label}</div><div class="col-sm-8">{input}{error}{hint}</div>'])
    ->textInput();

echo "<div class='clearfix'></div>";
echo Html::submitButton('Создать', ['class' => 'btn btn-success   ', 'id' => 'addSubmit', 'data-toggle' => 'tooltip', 'data-placement' => 'top', 'data-html' => 'true',]);
ActiveForm::end();

?>

<script><?=file_get_contents(Yii::getAlias('@webroot') . '/js/handleModalForm.js')?></script>
