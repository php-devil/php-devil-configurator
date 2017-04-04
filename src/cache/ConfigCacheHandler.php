<?php
namespace PhpDevil\Common\Configurator\cache;

abstract class ConfigCacheHandler
{
    /**
     * Хранилище кешированных конфигураций
     * @var array
     */
    protected $storage = [];
    /**
     * Проверка наличия конфигурации по хеш-коду
     *
     * @param $hash
     * @return bool
     */
    public function isStored($hash)
    {
        return isset($this->storage[$hash]);
    }
    /**
     * Сохранение конфигурации в хранилище
     *
     * @param $hash
     * @param $data
     */
    public function store($hash, $data)
    {
        $this->storage[$hash] = $data;
    }
    /**
     * Восстановление информации из хранилища
     *
     * @param $hash
     * @return array
     */
    public function restore($hash)
    {
        if ($this->isStored($hash)) {
            return $this->storage[$hash];
        } else {
            return [];
        }
    }
}