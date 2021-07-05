<?php
// прочитаю настройки из файла
$file = __DIR__ . '\\..\\priv\\mail_settings';
if (!is_file($file)) {
    // создаю файл
    file_put_contents($file, "test\ntest\ntest");
}
$content = file_get_contents($file);
$settingsArray = mb_split("\n", $content);

return ['address' => $settingsArray[0], 'login' => $settingsArray[1], 'password' => $settingsArray[2]];