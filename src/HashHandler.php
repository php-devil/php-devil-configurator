<?php
namespace PhpDevil\Common\Configurator;

class HashHandler
{
    /**
     * Ранее рассчитанные хеш-коды для предотвращения многократных
     * рассчетов одних и тех же значений
     *
     * @var array
     */
    private $calculatedHashes = [];
    /**
     * Получение хеш-кода значения
     *
     * Хеш рассчитывается только в первый раз, при последующих обращениях возвращается ранее
     * рассчитанный хеш из массива $this->calculatedHashes
     *
     * @param $someValue
     *
     * @return string
     */
    public function calculate($someValue)
    {
        if (!isset($this->calculatedHashes[$someValue])) {
            $this->calculatedHashes[$someValue] = md5($someValue);
        }
        return $this->calculatedHashes[$someValue] = md5($someValue);
    }
    private static $_instance = null;
    private function __construct(){}
    private function __clone(){}
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }
}