<?php

use yii\db\Connection;

require_once dirname(__DIR__) . '/priv/Info.php';

return [
    'class' => Connection::class,
    'dsn' => 'mysql:host=localhost;dbname=u1208375_oblepiha',
    'username' => \app\priv\Info::DB_LOGIN,
    'password' => \app\priv\Info::DB_PASSWORD,
    'charset' => 'utf8',

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
