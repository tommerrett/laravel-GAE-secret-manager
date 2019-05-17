# Laravel Google App Engine (GAE) Secret Manager #
Manage secrets when deploying Laravel to Google App Engine (Standard or Flexible). Store all secrets in Google Datastore.

## Installation
1. Require this package in the `composer.json` of your Laravel project. This will download the package.

```
composer require tommerrett/laravel-gae-secret-manager
```

The `Maatwebsite\Excel\ExcelServiceProvider` is auto-discovered and registered by default, but if you want to register it yourself:

Add the ServiceProvider in config/app.php
```
'providers' => [
    /*
     * Package Service Providers...
     */
    tommerrett\LaravelGAESecretManager\GAESecretsServiceProvider::class,
]
```
2. To publish the config, run the vendor publish command:
```
php artisan vendor:publish --provider="tommerrett\LaravelGAESecretManager\GAESecretsServiceProvider"
```
This will create a new config file named config/GAESecrets.php.

Here you can set the functionality of the package. You will need to define the ENV variables that you wish to store in datastore by setting the `variables` array.

Set which environment should use datastore to store the secrets by providing an array in `enabled-environments`.

Some env() variables are stored in the config and as such we need to define if config files also need updating with the datastore values, this is done by setting the `variables-config` array. For example if you store 'APP_KEY' in datastore you will need to add the following to the `variables-config` option: `'APP_KEY' => 'app.key'`

Caching - Datastore requests can add an additional 100-250ms of latency to each request. It is reccomended to use caching to significantly reduce this latency. File based caching is enabled by default. Set the remaining config settings to customise the caching undertaken by the package.

## Configuring Datastore
You create a new entity in Google Datastore called `Parameters` (case sensitive). Follow the below instructions for how to do this:
1. Go to: `https://console.cloud.google.com/datastore/entities?project=<project-name>`, if this is the first time using Datastore then you must select datastore mode (not firestore - as firestore is not supported).
2. Create a new `Entity` named `Parameters`
3. Add a new property `name` to the Entity (type: string) and enter your environment variable name
4. Add a new property `value` to the Entity (type: string) and enter the value
5. Save the Entity
6. Repeat to enter all of the environment variables you want to store in Datastore.
