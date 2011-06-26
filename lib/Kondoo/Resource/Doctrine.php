<?php

namespace Kondoo\Resource;

use \Kondoo\Loader;
use \Kondoo\Util\PathUtil;
use \Kondoo\Options;
use \Doctrine\Common\Cache\ArrayCache;
use \Doctrine\Common\Cache\ApcCache;
use \Doctrine\Common\Cache\MemcacheCache;
use \Doctrine\Common\Cache\XcacheCache;
use \Doctrine\ORM\EntityManager;
use \Doctrine\ORM\Configuration;

class Doctrine implements Provider {
    
    private $config;
    
    private $options;
    
    private $entityManager;
    
    public function setOptions(array $options)
    {
        $doctrineLoader = new \Doctrine\Common\ClassLoader('Doctrine');
        Loader::registerLoader('Doctrine', array($doctrineLoader, 'loadClass'));
        
        $symfonyLoader = new \Doctrine\Common\ClassLoader('Symfony');
        Loader::registerLoader('Symfony', array($symfonyLoader, 'loadClass'));
        
        $this->options = $options;
        $this->config = new Configuration;
        $cacheType = self::$this->getOption('cache.type', 'ArrayCache');
        
        switch($cacheType) {
            case 'MemcacheCache':
                $server = $this->getOption('cache.server', 'localhost');
                $port = $this->getOption('cache.port', 11211);
                $memcache = new \Memcache();
                $memcache->connect($server, $port);
                $cache = new MemcacheCache;
                $cache->setMemcache($memcache);
            case 'ApcCache':   
                $cache = new ApcCache;
                break;
            case 'XcacheCache':
                $cache = new XcacheCache;
            case 'ArrayCache': 
            default:
                $cache = new ArrayCache; 
                break;
        }
        $this->config->setMetadataCacheImpl($cache);
        $this->config->setQueryCacheImpl($cache);
        $this->config->setResultCacheImpl($cache);
        
        $entitiesLocation = realpath(PathUtil::expand(Options::get('app.dir.models', './models')));
        if($entitiesLocation === false) {
            throw new Exception(_("Directory for models does not exist."));
        }
        $ormDriver = $this->config->newDefaultAnnotationDriver($entitiesLocation);
        $this->config->setMetadataDriverImpl($ormDriver);
        
        $generatedDir = $this->getOption('app.dir.generated', '../cache');
        $proxyDir = PathUtil::expand($generatedDir) . DIRECTORY_SEPARATOR . 'Generated/Proxies';
        $this->config->setProxyDir(PathUtil::expand($proxyDir));
        $this->config->setProxyNamespace('Generated\\Proxies');
        
        $generatedLoader = new \Doctrine\Common\ClassLoader('Generated', $generatedDir);
        Loader::registerLoader('Generated', array($generatedLoader, 'loadClass'));
        
        $generateProxies = self::getOption('orm.auto_generate_proxy', false);
        $this->config->setAutoGenerateProxyClasses($generateProxies);
    }
    
    public function em()
    {
        if(is_null($this->entityManager) || !$this->entityManager->isOpen()) {
            $this->entityManager = EntityManager::create($this->getOption('dbal'), $this->config);
        }
        return $this->entityManager;
    }
    
    private function getOption($item, $default = null)
    {
        $parts = explode('.', $item);
        $current =& $this->options;
        foreach($parts as $part) {
            if(!isset($current[$part])) {
                return $default;
            } else {
                $current =& $current[$part];
            }
        }
        return $current;
    }
}