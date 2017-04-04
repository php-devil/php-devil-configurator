<?php
namespace PhpDevil\Common\Configurator\cache;
use PhpDevil\Common\Configurator\HashHandler;

class FileCache extends ConfigCacheHandler
{
    /**
     * Путь к реестру кешированных конфигураций
     * @var string
     */
    private $listFilePath;
    /**
     * Путь к директори  кешей конфигурационных файлов
     * @var string
     */
    private $saveDirPath;
    /**
     * Время актуализации реестра кешированных конфигураций
     * @var integer
     */
    private $registryActualTime;
    /**
     * Проверка наличия конфигурации по хеш-коду
     *
     * @param $hash
     * @return bool
     */
    public function isStored($hash)
    {
        $modified = file_exists($hash) ? filemtime($hash) : 0;
        if ($modified) {
            $this->updateRegistry();
            $md5hash = HashHandler::getInstance()->calculate($hash);
            if (isset($this->storage[$md5hash])) {
                if (0 === $modified) return false;
                if ($this->storage[$md5hash] >= $modified) {
                    return true;
                }
            }
        }
        return false;
    }
    /**
     * Сохранение конфигурации в хранилище
     *
     * @param $hash
     * @param $data
     * @throws \Exception
     */
    public function store($hash, $data)
    {
        $modified = file_exists($hash) ? filemtime($hash) : 0;
        if ($modified) {
            $this->updateRegistry();
            $md5hash = HashHandler::getInstance()->calculate($hash);
            if (isset($this->storage[$md5hash])) {
                $oldFileName = $md5hash . '_' . $this->storage[$md5hash] . '.php';
                if (file_exists($this->saveDirPath . '/' . $oldFileName)) {
                    unlink($this->saveDirPath . '/' . $oldFileName);
                }
            }
            $newFileName = $md5hash . '_' . $modified . '.php';
            if ($f = fopen($this->saveDirPath . '/' . $newFileName, 'w')) {
                fputs($f, '<?php return ' . var_export($data, true) . ';');
                fclose($f);
                $this->storage[$md5hash] = $modified;
            } else {
                throw new \Exception('Can not write to config cache directory');
            }
            $this->dumpRegistry();
        }
    }
    /**
     * Восстановление информации из хранилища
     *
     * @param $hash
     * @return array
     */
    public function restore($hash)
    {
        $this->updateRegistry();
        $md5Hash = HashHandler::getInstance()->calculate($hash);
        if (isset($this->storage[$md5Hash])) {
            $fileName = $this->saveDirPath . '/' . $md5Hash . '_' . $this->storage[$md5Hash] . '.php';
            if (file_exists($fileName)) {
                return require $fileName;
            }
        }
        return [];
    }
    /**
     * Обновление реестра кешированных конфигураций
     */
    private function updateRegistry()
    {
        if (!file_exists($this->listFilePath)) {
            $this->dumpRegistry();
        } else {
            $actualization = filemtime($this->listFilePath);
            if ($actualization > $this->registryActualTime) {
                $this->storage = require $this->listFilePath;
                $this->registryActualTime = $actualization;
            }
        }
    }
    /**
     * Сохранение реестра кешированных конфигураций
     * @throws FilesException
     */
    private function dumpRegistry()
    {
        if ($f = fopen($this->listFilePath, 'w')) {
            fputs($f, '<?php return ' . var_export($this->storage, true) . ';');
            fclose($f);
            $this->listActual = filemtime($this->listFilePath);
        } else {
            throw new FilesException(FilesException::NOT_ENOUGH_PRIV_F, ['path' => $this->listFilePath]);
        }
    }

    public function __construct($cacheDir)
    {
        if (is_dir($cacheDir)) {
            $this->listFilePath = $cacheDir . '/0_registry.php';
            $this->saveDirPath  = $cacheDir;
            $this->updateRegistry();
        } else {
            throw new \Exception("Config handler error: cache directory not exists");
        }
    }
}