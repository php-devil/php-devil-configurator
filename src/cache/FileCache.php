<?php
namespace PhpDevil\Common\Configurator\cache;

class FileCache extends ConfigCacheHandler
{
    private $cacheDir;

    public function __construct($cacheDir)
    {
        if (is_dir($cacheDir)) {
            $this->cacheDir = $cacheDir;
        } else {
            throw new \Exception("Config handler error: cache directory not exists");
        }
    }
}