<?php
/**
 * Created by PhpStorm.
 * User: eldor
 * Date: 17.12.2018
 * Time: 10:38
 */

namespace app\models\utils;


use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use Exception;
use yii\base\InvalidArgumentException;
use yii\base\Model;

class DOMHandler extends Model
{

    public $dom;
    private $xpath;

    public function __construct($xml, array $config = [])
    {
        parent::__construct($config);
        // в параметрах ищу строку с xml
        $this->dom = self::getDom($xml);
        $this->xpath = self::getXpath($this->dom);
    }

    /**
     * @param $domElement DOMNode
     * @return array
     */
    public static function getElemAttributes($domElement): array
    {
        $attributes = $domElement->attributes;
        $answer = [];
        foreach ($attributes as $attribute){
            $answer[$attribute->nodeName] = $attribute->nodeValue;
        }
        return $answer;
    }

	/**
	 * @param $domElement DOMElement
	 * @param $attributes array
	 * @return DOMElement
	 */
	public static function setElemAttributes($domElement, $attributes): DOMElement
    {
        foreach ($attributes as $key => $value){
            $domElement->setAttribute($key, $value);
        }
        return $domElement;
    }

    /**
     * @param $domString string
     * @return DOMDocument
     */
    public static function getDom($domString): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        try{
            $dom->loadXML($domString);
        }
        catch (Exception $e){
            throw new InvalidArgumentException('Не удалось загрузить структуру документа.');
        }
        return $dom;
    }

    /**
     * @param $dom DOMDocument
     * @return DOMXpath
     */
    public static function getXpath($dom): \DOMXpath
    {
        try{
            $xpath = new \DOMXpath($dom);
        }
        catch (Exception $e){
            throw new InvalidArgumentException('Не удалось загрузить структуру документа.');
        }
        return $xpath;
    }

    /**
     * @param $item DOMElement
     * @param $attributeName string
     * @return float
     */
    public static function getFloatAttribute($item, $attributeName)
    {
        return CashHandler::toRubles($item->getAttribute($attributeName));
    }

    /**
	 * @param $xml DOMDocument
	 * @return string
	 */
	public static function saveXML($xml): string
	{
		return html_entity_decode($xml->saveXML($xml->documentElement));
    }
    public static function getXMLValues($str): array
    {
		$answer = [];
		$dom = self::getDom($str);
		$elems = $dom->documentElement->childNodes;
		foreach ($elems as $elem){
			/** @var DOMNode $elem */
			$answer[$elem->tagName] = $elem->nodeValue;
		}
		return $answer;
    }

    public function findByName($name)
    {
        return $this->xpath->query('//' . $name);
    }

    /**
     * @param $expr string
     * @return DOMNodeList
     */
    public function query($expr): DOMNodeList
    {
        return $this->xpath->query($expr);
    }

    public function save()
    {
        return self::saveXML($this->dom);
    }

    /**
     * @param string $string
     * @return DOMElement
     */
    public function createElement(string $string): DOMElement
    {
        return $this->dom->createElement($string);
    }

}