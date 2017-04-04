<?php
namespace PhpDevil\Common\Configurator;
use PhpDevil\Common\Configurator\cache\FileCache;
use PhpDevil\Common\Configurator\cache\RamCache;

/**
 * Class Loader
 * Загрузчик конфигураций.
 * По умолчанию загруженные конфигурации хранятся в памяти (изменение файлов не отслеживается).
 * Дополнительно можно подключить возможность кеширования на диске.
 * @package PhpDevil\Common\Configurator
 */
class Loader
{
    private static $instance = null;

    /**
     * Директория для файлового кеша конфигураций. Обязательна для отслеживания изменений
     * в конфигурационных файлов
     * @var null
     */
    private $fileCacheDir = null;

    /**
     * Стек подключенных хендлеров кеширования
     * @var null
     */
    private $cacheStack = null;

    private $cacheDepth = 0;

    /**
     * Подключение файлового кеширования конфигураций.
     * @param string $cacheDirectory
     */
    public function enableFileCaching($cacheDirectory)
    {
        $this->cacheStack[] = $this->cacheStack[] = ['name' => 'ran', 'class' => new FileCache($cacheDirectory)];
        $this->cacheDepth = count($this->cacheStack);
    }

    /**
     * Загрузка конфигурации, сохранение
     * @param  $pathToFile
     * @return array
     */
    public function load($pathToFile)
    {
        $config = [
            'data' => null,
            'extra' => [
                'isUpdated'  => false,
                'fileName'   => str_replace('\\', '/', $pathToFile),
                'fileExists' => file_exists($pathToFile)]
        ];

        $this->searchCache($pathToFile, $config);



        return $config;
    }

    /**
     * Поиск кешированного конфигурационного массива
     *
     * @param $pathToFile;
     * @param $config;
     */
    protected function searchCache($pathToFile, &$config)
    {
        $cacheIndex = 0;
        if (!isset($config['data'])) {
            $config['data'] = null;
        }
        if (!is_array($config['extras']['cache'])) {
            $config['extras']['cache'] = [];
        }
        if (is_array($this->cacheStack)) {
            for ($cacheIndex = 0; $cacheIndex < $this->cacheDepth; $cacheIndex++) {
                $cache = $this->getCacheClass($cacheIndex);
                if ($cache->isStored($pathToFile)) {
                    $config['data'] = $cache->restore($pathToFile);
                    $this->addCacheExtras($config, $cacheIndex, 'restored');
                    break;
                } else {
                    $this->addCacheExtras($config, $cacheIndex, 'not found');
                }
            }
        }
        $this->saveCache($config, $pathToFile, $cacheIndex);
    }
    /**
     * Сохранение данных в вышестоящих процессорах кеширования
     *
     * @param $config
     * @param $pathToFile
     * @param $cacheIndex
     */
    protected function saveCache(&$config, $pathToFile, $cacheIndex)
    {
        if (null !== $config['data']) while ($cacheIndex > 0){
            --$cacheIndex;
            $cache = $this->getCacheClass($cacheIndex);
            $cache->store($pathToFile, $config['data']);
            $this->addCacheExtras($config, $cacheIndex, 'stored');
        }
    }
    /**
     * Добавление к конфигурации отчета о ее кешировании
     *
     * @param $config
     * @param $cacheIndex
     * @param $message
     */
    protected function addCacheExtras(&$config, $cacheIndex, $message)
    {
        $cacheName = $this->cacheStack[$cacheIndex]['name'];
        if (!is_array($config['extras']['cache'][$cacheName])) {
            $config['extras']['cache'][$cacheName] = [];
        }
        $config['extras']['cache'][$cacheName][] = $message;
    }

    private function __construct()
    {
        $this->cacheStack[] = $this->cacheStack[] = ['name' => 'ran', 'class' => new RamCache];
        $this->cacheDepth = count($this->cacheStack);
    }

    public static function getInstance()
    {
        if (null === self::$instance) self::$instance = new self;
        return self::$instance;
    }
}