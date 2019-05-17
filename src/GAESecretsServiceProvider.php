<?php

namespace tommerrett\LaravelGAESecretManager;

use Illuminate\Support\ServiceProvider;
use Google\Cloud\Datastore\DatastoreClient;
use Illuminate\Support\Facades\Cache;

class GAESecretsServiceProvider extends ServiceProvider
{
    //Define variables

    protected $variables;

    protected $configVariables;

    protected $cache;

    protected $cacheExpiry;

    protected $cacheStore;

    protected $enabledEnvironments;

    //Set variables on class construction from config

    public function __construct () {

        $this->variables = config('GAESecrets.variables');
    
        $this->configVariables = config('GAESecrets.variables-config');
    
        $this->cache = config('GAESecrets.cache-enabled', true);
    
        $this->cacheExpiry = config('GAESecrets.cache-expiry', 0);
    
        $this->cacheStore = config('GAESecrets.cache-store', 'file');
        
        $this->enabledEnvironments = config('GAESecrets.enabled-environments');
    
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //No classes need registration
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/config.php' => config_path('GAESecrets.php'),
        ]);

        //Load secrets
        $this->LoadSecrets();
    }

    protected function LoadSecrets()
    {
        //load vars from datastore to env

        //Only run this if the evironment is enabled in the config
        if(in_array(env('APP_ENV'), $this->enabledEnvironments))
        {
            if($this->cache)
            {

                if(!$this->checkCache())
                {
                    //Cache has expired need to refresh the cache from Datastore
                    $this->getVariables();
                }
            }
            else
            {
                $this->getVariables();
            }
        
            //Process variables in config that need updating
            $this->updateConfigs();
    
        }
    }


    protected function checkCache()
    {
        foreach($this->variables as $variable)
        {
            $val = Cache::store($this->cacheStore)->get($variable);
            if (!is_null($val)) 
            {    
                putenv("$variable=$val");
            }
            else
            {
                return false;
            }
        }
        return true;
    }

    protected function getVariables()
    {
        try{
            $datastore = new DatastoreClient();
            
            $query = $datastore->query();
            $query->kind('Parameters');
            
            $res = $datastore->runQuery($query);
            foreach ($res as $parameter) {
                $name = $parameter['name'];
                $val = $parameter['value'];
                putenv("$name=$val");
                $this->storeToCache($name, $val);
            } 
        }
        catch (\Exception $e) {
            // Nothing, this is normal
        }
        
    }

    protected function updateConfigs()
    {
        foreach($this->configVariables as $variable => $configPath)
        {
            config([$configPath => env($variable)]);
        }
    }

    protected function storeToCache($name, $val)
    {
        if($this->cache)
        {
            Cache::store($this->cacheStore)->put($name, $val, now()->addMinutes($this->cacheExpiry));
        }
    }


}