Composer is a preferred way for handling your autoloading:


      <https://github.com/laminas/laminas-mvc-skeleton/blob/8beddc599ea56406abc79d06409f83fcc2cc7350/composer.json#L19-L22|github.com>
  


If you used new skeleton application as a basis for migration, then yes, it is possible that `Module::getAutoloaderConfig()` is not used. You would need to enable loader usage in configuration as it is turned off by default:


      <https://github.com/laminas/laminas-mvc-skeleton/blob/8beddc599ea56406abc79d06409f83fcc2cc7350/config/application.config.php#L16|github.com>
  
