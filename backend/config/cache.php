<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default cache connection that gets used while
    | using this caching library. This connection is used when another is
    | not explicitly specified when executing a given caching function.
    |
    | Supported: "apc", "array", "database", "file", "memcached", "redis"
    |
    */

    'default' => env('CACHE_DRIVER', 'file'),

    'limiter' => env('LIMITER_CACHE_DRIVER', env('CACHE_DRIVER', 'file')),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    |
    | Here you may define all of the cache "stores" for your application as
    | well as their drivers. You may even define multiple stores for the
    | same cache driver to group types of items stored in your caches.
    |
    */

    'stores' => [

        'apc' => [
            'driver' => 'apc',
        ],

        'array' => [
            'driver' => 'array',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'cache',
            'connection' => null,
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
        ],

        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT  => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],
        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
        ],
        'redis_limiter' => [
            'driver' => 'redis',
            'connection' => 'limiter',
//            'prefix' => ''
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    |
    | When utilizing a RAM based store such as APC or Memcached, there might
    | be other applications utilizing the same cache. So, we'll specify a
    | value to get prefixed to all our keys so we can avoid collisions.
    |
    */

    'prefix' => env(
        'CACHE_PREFIX',
        \Illuminate\Support\Str::slug(env('APP_NAME', 'laravel'), '_') . '_cache'
    ),

    /*
    |--------------------------------------------------------------------------
    | Cache Serializable Classes
    |--------------------------------------------------------------------------
    |
    | List PHP classes that are allowed to be unserialized from cache. The
    | stores here (DatabaseStore, FileStore, etc.) pass this straight to
    | PHP's unserialize(..., ['allowed_classes' => ...]) whenever it is not
    | null — and PHP's `false` there means "reject every object", not
    | "allow all" as an earlier version of this comment claimed. That
    | mismatch turned every cached Collection/Eloquent model/stdClass into
    | __PHP_Incomplete_Class on read (see Modules::getModulesSettings()).
    | Use null (unrestricted, matches this app's actual cache usage) or an
    | array of class names to restrict deserialization.
    |
    */

    'serializable_classes' => null,

];
