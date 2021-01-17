# Laravel .env visual installer

![Set params page](scr1.png)

## Capabilities

- Make & filling `.env` file
- Checking connect with MySql database
- Filling the database from the specified dump
- Creating a symbolic link to the storage (`php artisan storage:link`)

## Usage

1. Download/place the files of this project in a folder `public/install/` your Laravel project.

2. Edit file `/public/index.php`

    After `$app = require_once __DIR__.'/../bootstrap/app.php';` add code:
    
    ```php
    //...
    $app = require_once __DIR__.'/../bootstrap/app.php';
    
    if (!file_exists(__DIR__.'/../.env')) {
        require_once __DIR__.'/../public/install/index.php';
        exit();
    }
    //...
    ```

3. If needed, save to `/database/dumps/` your database SQL-dumps.

4. Check if exists `.env.example` file.
 
5. Delete `.env` (if exists) file for start/restart installation.

6. Visit your site for start installing steps

Enjoy 😎
 
