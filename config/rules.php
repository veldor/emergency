<?php

return [
    'api' => 'api/do',
    'login' => 'site/login',
    'get/form/<type:\S+>' => 'management-actions/get-form',
    'delete-cottage/<id:\S+>' => 'management-actions/delete-cottage',
    'change-password/<id:\S+>' => 'management-actions/change-password',
    'alert/<action:\w+>' => 'user-actions/alert',
];