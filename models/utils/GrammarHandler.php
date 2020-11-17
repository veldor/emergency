<?php


namespace app\models\utils;


use DateTime;

class GrammarHandler
{
    public static array $months = ['Января', 'Февраля', 'Марта', 'Апреля', 'Мая', 'Июня', 'Июля', 'Августа', 'Сентября', 'Октября', 'Ноября', 'Декабря',];
    public static function startsWith($haystack, $needle): bool
    {
        return stripos(mb_strtolower($haystack, 'UTF-8'), mb_strtolower($needle, 'UTF-8')) === 0;
    }
    /**
     * @param $string
     * @return array|string
     */
    public static function personalsToArray($string)
    {
        // извлекаю имя и отчество из персональных данных
        $result = explode(' ', $string);
        if (count($result) === 3) {
            return ['lname' => $result[0], 'name' => $result[1], 'fname' => $result[2]];
        }
        return $string;
    }

    /**
     * <b>Получение имени и отчества пользователя</b>
     * @param $name
     * @return string|null
     */
    public static function getUserIo($name)
    {
        if ($data = self::personalsToArray($name)) {
            if (is_array($data)) {
                return "{$data['name']} {$data['fname']}";
            }
            return $data;

        }
        return $name;
    }

    public static function dayTimeGreetings(): string
    {
        $hour = date('H');
        if($hour < 7){
            return "Доброй ночи";
        }
        if($hour < 12){
            return "Доброе утро";
        }
        if($hour < 20){
            return "Добрый день";
        }
        return "Добрый вечер";
    }

    public static function convertTemperature(int $external_temperature): ?string
    {
        if($external_temperature > 127){
            return '<b class="text-info">-' . (256 - $external_temperature) . "С<sup>0</sup></b>";
        }
        return '<b class="text-success">' . $external_temperature . "С<sup>0</sup></b>";
    }
    public static function simpleConvertTemperature(int $external_temperature): ?string
    {
        if($external_temperature > 127){
            return '-' . (256 - $external_temperature) . " гр.";
        }
        return $external_temperature . " гр.";
    }

    public static function timestampToDate(int $timestamp): string
    {
        $date = new DateTime();
        $date->setTimestamp($timestamp);
        $answer = '';
        $day = $date->format('d');
        $answer .= $day;
        $month = mb_strtolower(self::$months[$date->format('m') - 1]);
        $answer .= ' ' . $month . ' ';
        $answer .= $date->format('Y') . ' года.';
        $answer .= $date->format(' H:i:s');
        return $answer;
    }

    public static function handleCounterData(int $current_counter_indication): string
    {
        return round($current_counter_indication / 10,1). ' кВт⋅ч';
    }
}