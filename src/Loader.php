<?php
namespace PhpDevil\Common\Configurator;
use PhpDevil\Common\Configurator\cache\FileCache;
use PhpDevil\Common\Configurator\cache\RamCache;
use Symfony\Component\Yaml\Yaml;

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
        $this->cacheStack[] = ['name' => 'files', 'class' => new FileCache($cacheDirectory)];
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
            'extras' => [
                'isUpdated'  => false,
                'fileName'   => str_replace('\\', '/', $pathToFile),
                'fileExists' => file_exists($pathToFile)]
        ];

        $this->searchCache($pathToFile, $config);
        if (null === $config['data']) {
            $config['extras']['isUpdated'] = true;
            if (file_exists($pathToFile)) {
                switch (substr($pathToFile, strrpos($pathToFile, '.'))) {
                    case '.yml':
                        $config['data'] = Yaml::parse(file_get_contents($pathToFile));
                        break;

                    case '.json':
                        $data = file_get_contents($pathToFile);
                        $config['data'] = json_decode($data, true);
                        break;

                    default:
                        $config['data'] = require $pathToFile;
                }

                $this->saveCache($config, $pathToFile, $this->cacheDepth);
            } else {
                $config['data'] = [];
            }
        } else {
        }
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
     * Получение класса процессора кеширования
     *
     * @param $cacheIndex
     * @return mixed
     */
    protected function getCacheClass($cacheIndex)
    {
        return $this->cacheStack[$cacheIndex]['class'];
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
        $this->cacheStack[] = ['name' => 'ram', 'class' => new RamCache];
        $this->cacheDepth = count($this->cacheStack);
    }

    public static function getInstance()
    {
        if (null === self::$instance) self::$instance = new self;
        return self::$instance;
    }
}