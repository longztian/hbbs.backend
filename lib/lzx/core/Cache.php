<?php

namespace lzx\core;

class Cache
{ // static class instead of singleton
   // here we don't use memory cache at all, becaue file cache is memory cache in Linux

   const RM = '/bin/rm';
   const NOHUP = '/usr/bin/nohup';
   const BASH = '/bin/bash';
   const MKDIR = '/bin/mkdir';
   const XARGS = '/usr/bin/xargs';
   const AWK = '/usr/bin/awk';
   const GREP = '/bin/grep';

   public static $status = FALSE;
   public $path;
   private $logger;
   private $public_zone;
   private $private_zone;
   private $map_zone;

   private function __construct($path)
   {
      $this->path = $path;

      $this->public_zone = $path . '/public';
      $this->private_zone = $path . '/private';
      $this->map_zone = $path . '/map';

      if (!is_dir($this->public_zone))
      {
         \mkdir($this->public_zone, 0755, TRUE);
      }
      if (!is_dir($this->private_zone))
      {
         \mkdir($this->private_zone, 0755, TRUE);
      }
      if (!is_dir($this->map_zone))
      {
         \mkdir($this->map_zone, 0755, TRUE);
      }
   }

   /*
    * @return \lzx\core\Cache
    */

   public static function getInstance($path = NULL)
   {
      static $instance;

      if (!isset($instance))
      {
         if (\is_null($path))
         {
            throw new \Exception('error: no instance available. A cache path is required to create a new cache instance');
         }
         $instance = new self($path);
      }

      return $instance;
   }

   public function setLogger(Logger $logger)
   {
      $this->logger = $logger;
   }

   public function getStatus()
   {
      return self::$status;
   }

   public function setStatus($status)
   {
      self::$status = (bool) $status;
   }

   //control page cache ttl, could be different for different url and different roles, set = 0 to disable file cache for current page
// passing reference is only for paramater modification, will have low performance
   public function store($key, $data, $public = FALSE)
   {
      // never save content to cache file when MySQL has error
      if (!self::$status)
      {
         return;
      }

      if ($public)
      { // gzip data for public cache file used by webserver
         $data = \gzencode($data, 6); // use 6 as default and equal to webserver gzip compression level
      }

      $fn = $this->_getFileName($key, $public);
      return $this->_write($fn, $data);
   }

// The function to fetch data returns false on failure
   public function fetch($key)
   { // only fetch unzipped files from private zone
      if (!self::$status)
      {
         return FALSE;
      }

      $fn = $this->_getFileName($key, FALSE); // always fetch from private zone, webserver will use the public zone

      return $this->_read($fn); // return FALSE on failure
   }

   /*
    * php only fetch page from private zone
    */

   public function fetchPage()
   {
      if ($_SERVER['REQUEST_METHOD'] === 'GET')
      {
         return $this->fetch($_SERVER['REQUEST_URI']);
      }

      return FALSE;
   }

   /*
    * can store page in public zone or private zone
    */

   public function storePage($data, $public = TRUE)
   {// passing reference is only for paramater modification, will have low performance
      if ($_SERVER['REQUEST_METHOD'] === 'GET')
      {
         $this->store($_SERVER['REQUEST_URI'], $data, $public);
      }
   }

   public function delete($key)
   {
      // one small bug would be cocurency, child get deleted here but generated by another client process, while this content haven't get deleted yet.
      // so all maps and caches will break, after this node and map get deleted
      // FIRST: delete self
      $_key = $this->_cleanKey($key);
      if ($_key[0] === '/')
      { // page cache, //robots don't have pages in private zone
         $files = $this->public_zone . $_key . '*.html.gz ' . $this->private_zone . $_key . '*.html'; // include get parameters
      }
      else
      { // fragment, only stored in private zone
         $files = $this->private_zone . '/' . $_key . '.txt';
      }
      $pmap = $this->_getMapName($key, FALSE);
      $cmap = $this->_getMapName($key);

      $children = $this->fetchMap($key);
      $this->_runCommand(self::RM . ' -rf ' . $files . ' ' . $pmap . ' ' . $cmap);

      // SECOND: recuservely delete child
      foreach ($children as $child)
      {
         $this->delete($child);
      }
   }

   public function storeMap($key, $mapCacheKey)
   {
      if (!self::$status)
      {
         return;
      }

      $children = $this->_fetchRawMap($key);
      if (\in_array($mapCacheKey, $children))
      {
         $c_stored = TRUE;
      }
      else
      {
         $children[] = $mapCacheKey;
         $fn_cmap = $this->_getMapName($key);
         $c_stored = $this->_write($fn_cmap, \implode(\PHP_EOL, $children));
      }

      $parents = $this->_fetchRawMap($mapCacheKey, FALSE);
      if (\in_array($key, $parents))
      {
         $p_stored = TRUE;
      }
      else
      {
         $parents[] = $key;
         $fn_pmap = $this->_getMapName($mapCacheKey, FALSE);
         $p_stored = $this->_write($fn_pmap, \implode(\PHP_EOL, $parents));
      }

      return ($c_stored && $p_stored);
   }

   public function fetchMap($key, $childMap = TRUE)
   {
      $keys = array();
      // get all possible keys
      foreach ($this->_fetchRawMap($key, $childMap) as $k)
      {
         // verify the key is in its children's parent list or its parents' child list
         if (\in_array($key, $this->_fetchRawMap($k, !$childMap)))
         {
            $keys[] = $k;
         }
      }
      return $keys;
   }

   private function _fetchRawMap($key, $childMap = TRUE)
   {
      $fn = $this->_getMapName($key, $childMap);

      $lines = \trim($this->_read($fn));
      return ($lines ? \array_unique(\explode(\PHP_EOL, $lines)) : array());
   }

   public function clearAllCache()
   {
      $cmd = 'rm -rf ' . $this->path . '/* && mkdir -p ' . $this->path . '/{public,private,map}';
      $this->_runCommand($cmd);
      $this->logger->info("clear all cache\n[CMD] " . $cmd);
   }

   private function _getMapName($key, $childMap = TRUE)
   {
      static $fileNames = array();

      $cache_key = \trim($key);

      if (!\array_key_exists($cache_key, $fileNames))
      {
         $_key = $cache_key;
         if (\strlen($_key) == 0 || \strpos($_key, ' ') !== FALSE)
         {
            throw new \Exception('invalid cache key : ' . $key);
         }

         // page uri or fragment file key
         $_key = ($_key[0] === '/' ? 'p_' : 'f_') . trim($_key, '/');
         $_key = \preg_replace('/[^0-9a-z\.\_\-]/i', '_', $_key);

         $fileName[$cache_key] = $this->map_zone . '/' . $_key;
      }

      return $fileName[$cache_key] . ($childMap ? '.cmap' : '.pmap');
   }

   private function _read($file)
   {
      try
      {
         // read only if exist!!
         return \is_file($file) ? \file_get_contents($file) : FALSE;
         ;
      }
      catch (\ErrorException $e)
      {
         $this->logger->warn('Could not read cache file: ' . $e->getMessage());
         return FALSE;
      }
   }

   private function _write($file, $data)
   {
      try
      {
         if (\file_put_contents($file, $data, \LOCK_EX) !== FALSE)
         {
            return TRUE;
         }
         else
         {
            throw new \Exception($file);
         }
      }
      catch (\ErrorException $e)
      {
         $this->logger->warn('Could not write to cache file: ' . $e->getMessage());
         if (\disk_free_space($this->path) < 10240)
         {
            $this->logger->info("no free space left");
            $this->clearAllCache();
         }
         return FALSE;
      }
   }

   private function _runCommand($cmd)
   {
      $cmd = self::NOHUP . ' ' . self::BASH . ' -c "' . $cmd . '" 1 >> ' . $this->path . '/cache.log 2>&1 &'; // non-block command
      \shell_exec($cmd);
   }

   private function _getFileName($key, $public = FALSE)
   { // get private file name by default
      static $fileNames = array();
      $cache_key = ($public ? 'public_' : 'private_') . $key;

      if (!\array_key_exists($cache_key, $fileNames))
      {
         $_key = $this->_cleanKey($key);
         if ($_key[0] === '/')
         { // page
            if ($public)
            { // output gz files, for webserver read
               $fn = $this->public_zone . $_key . '.html.gz';
            }
            else
            { // use unzipped file, for php read and write
               $fn = $this->private_zone . $_key . '.html';
            }

            // create dir if not exist
            $dir = \dirname($fn);
            if (!\is_dir($dir))
            {
               \mkdir($dir, 0755, TRUE);
            }
         }
         else
         { // fragment only in private zone, so ignore $public
            if ($public)
            {
               throw new \Exception('error: public zone can not have cache fragment');
            }
            else
            {
               $fn = $this->private_zone . '/' . $_key . '.txt';
            }
         }
         $fileName[$cache_key] = $fn;
      }

      return $fileName[$cache_key];
   }

   private function _cleanKey($key)
   {
      static $keys = array();

      $_key = \trim($key);

      if (!\array_key_exists($_key, $keys))
      {
         if (\strlen($_key) == 0 || \strpos($_key, ' ') !== FALSE)
         {
            throw new \Exception('error cache key : ' . $key);
         }

         if ($_key[0] === '/')
         { // page uri
            $keys[$_key] = \strpos($_key, '?') ? \str_replace('?', '#', $_key) : ($_key . '#');
         }
         else
         { // fragment or map key
            $keys[$_key] = \preg_replace('/[^0-9a-z\.\_\-]/i', '_', $_key);
         }
      }

      return $keys[$_key];
   }

}

//__END_OF_FILE__