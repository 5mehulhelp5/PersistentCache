# Mage2 Module: MageStack Parsistent Cache
A robust Magento 2 module that provides a **persistent, isolated cache layer** untouched by Magento's default CLI cache operations (`cache:clean`, `cache:flush`).

## Requirements
- Magento 2.4.8
- PHP 8.4

## Module version
- 1.0.0

## Why This Module?
In Magento 2, we often store temporary but critical data in cache — such as serialized payloads, or flags for ongoing processes.
However, a major concern arises:
 - **Running Magento CLI commands like `bin/magento cache:flush` or `cache:clean` can wipe out these important entries**, potentially breaking business logic or corrupting workflows.

### Solution: Persistent, Isolated Cache
This module creates and manages a **completely isolated cache frontend** using its own identifier (`parsistent`). Magento CLI commands **do not affect it** — ensuring your critical data remains safe.

## Features
- **Immune to `cache:clean` and `cache:flush`**
- **Supports Valkey, File, and Database backends**
- **Flexible configuration via `env.php`**
- **Simple service interface (`CacheRepositoryInterface`)**
- **Tested with Valkey, File, and DB cache**
  
## Configuration
Add a new cache frontend in your `app/etc/env.php` under the `cache` key:

### Valkey
  ````
    'cache' => [
        'frontend' => [
            'parsistent' => [
                'id_prefix' => 'YOUR_PREFIX',
                'backend' => 'Magento\\Framework\\Cache\\Backend\\Redis',
                'backend_options' => [
                    'server' => 'VALKEY_HOST',
                    'database' => 'VALKEY_DB',
                    'port' => 'VALKEY_PORT',
                    'password' => '',
                    'compress_data' => '1',
                    'compression_lib' => '',
                    'use_lua' => '0',
                    'use_lua_on_gc' => '1'
                ]
            ]
        ]
    ],
  ````

### File
  ````
    'cache' => [
        'frontend' => [
            'parsistent' => [
                'id_prefix' => 'YOUR_PREFIX',
                'backend' => 'Magento\\Framework\\Cache\\Backend\\File',
                'backend_options' => [
                    'cache_dir' => BP . '/var/cache/parsistent'
                ]
            ]
        ]
    ],
  ````

### DB
  ````
    'cache' => [
        'frontend' => [
            'parsistent' => [
                'id_prefix' => 'YOUR_PREFIX',
                'backend' => 'Magento\\Framework\\Cache\\Backend\\Database',
                'backend_options' => [
                    'auto_create_tables' => true
                ]
            ]
        ]
    ],

  ````

## Installation
1. **Install the module via Composer**:
    To install this module, run the following command in your Magento root directory:
    - ``composer require mage-stack/module-persistent-cache``
2. **Enable the module:**
    After installation, enable the module by running:
   - ``php bin/magento module:enable MageStack_PersistentCache``
3. **Apply database updates:**
    Run the setup upgrade command to apply any database changes:
    - ``php bin/magento setup:upgrade``
4. **Flush the Magento cache:**
    Finally, flush the cache:
   -  ``php bin/magento cache:flush``

## Usage
Once installation is successfull invoke `MageStack\PersistentCache\Api\CacheRepositoryInterface` in your class and start using available endpoints.

## Contributing
If you would like to contribute to this module, feel free to fork the repository and create a pull request. Please make sure to follow the coding standards of Magento 2.

## Reporting Issues
If you encounter any issues or need support, please create an issue on the GitHub Issues page. We will review and address your concerns as soon as possible.

## License
This module is licensed under the MIT License.

## Support
If you find this module useful, consider supporting me By giving this module a star on github
