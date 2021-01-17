<?php

    define('BASE_DIR', __DIR__ . '/../..');
    define('ENV_EXAMPLE_PATH', BASE_DIR . '/.env.example');
    define('ENV_PATH', BASE_DIR . '/.env');
    define('SQL_IMPORT_DIR', BASE_DIR . '/database/dumps');
    define('LARAVEL_STORAGE_PUBLIC_PATH', BASE_DIR . '/storage/app/public');
    define('LARAVEL_PUBLIC_PATH', BASE_DIR . '/public');

$step = empty($_GET['step']) ? 'verification' : $_GET['step'];

    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'
        ? "https://"
        : "http://";
    $url = $protocol . $_SERVER['HTTP_HOST'];

    $result = [
        'success' => [],
        'errors' => [],
        'warnings' => [],
    ];


    if (!file_exists(ENV_EXAMPLE_PATH)) {
        $result['errors'][] = "File not found: " . ENV_EXAMPLE_PATH;
    }

    if (in_array($step, ['finish', 'install'])) {

        $sql_imports = [];
        if (is_dir(SQL_IMPORT_DIR)) {
            $sql_imports = array_filter(scandir(SQL_IMPORT_DIR), function ($item) {
                return is_file(SQL_IMPORT_DIR . '/' . $item);
            });
        }

        $app_url = isset($_POST['app_url']) ? $_POST['app_url'] : $url;
        $app_name = isset($_POST['app_name']) ? $_POST['app_name'] : '';
        $app_locale = isset($_POST['app_locale']) ? $_POST['app_locale'] : 'en';
        $db_host = isset($_POST['db_host']) ? $_POST['db_host'] : '127.0.0.1';
        $db_port = isset($_POST['db_port']) ? $_POST['db_port'] : '3306';
        $db_database = isset($_POST['db_database']) ? $_POST['db_database'] : '';
        $db_username = isset($_POST['db_username']) ? $_POST['db_username'] : '';
        $db_password = isset($_POST['db_password']) ? $_POST['db_password'] : '';
        $sql_import = isset($_POST['sql_import']) ? $_POST['sql_import'] : '';
    }

    if ($step === 'finish') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!file_exists(ENV_PATH)) {
                if (copy(ENV_EXAMPLE_PATH, ENV_PATH)) {

                    require __DIR__ . '/EnvManager.php';

                    $envManager = new EnvManager(ENV_PATH);
                    $envManager->setValue('APP_KEY', $envManager->generateRandomKey());

                    $env_keys = [
                        'app_name', 'app_url', 'app_locale', 'db_host', 'db_port',
                        'db_database', 'db_username', 'db_password',
                    ];

                    foreach ($env_keys as $key) {
                        $envManager->setValue($key, $_POST[$key] ?? '');
                        //$result['success'][] = $key . '=' . ($_POST[$key] ?? '');
                    }

                    // Test connect to Database
                    try {
                        $dsn = "mysql:host=$db_host;dbname=$db_database;charset=utf8mb4;port=$db_port";
                        $dbh = new PDO($dsn, $db_username, $db_password);

                        // Import the Database
                        if ($sql_import) {
                            $mysql_import_filename = SQL_IMPORT_DIR . '/' . $sql_import;
                            if (file_exists($mysql_import_filename)) {
                                $command = 'mysql -h'
                                    . $db_host
                                    . ' -u' . $db_username
                                    . ' -p' . $db_password
                                    . ' ' . $db_database
                                    . ' < '
                                    . $mysql_import_filename;
                                $output = [];
                                exec($command, $output, $worked);
                                switch ($worked) {
                                    case 0:
                                        $result['success'][] = "The data from the file [$mysql_import_filename] were successfully imported into the database [$db_database]";
                                        break;
                                    case 1:
                                        $result['errors'][] = "An error occurred during the import. Please check the imported file [$mysql_import_filename].";
                                        unlink(ENV_PATH);
                                        $step = 'install';
                                        break;
                                }
                            } else {
                                $result['errors'][] = "Database dump file [$mysql_import_filename] not found.";
                                unlink(ENV_PATH);
                                $step = 'install';
                            }
                        }

                        $laravel_storage_public_path = str_replace('public/install/../../', '', LARAVEL_STORAGE_PUBLIC_PATH);
                        if (!is_link(LARAVEL_PUBLIC_PATH . '/storage')) {
                            try {
                                if (!windows_os()) {
                                    symlink($laravel_storage_public_path, LARAVEL_PUBLIC_PATH . '/storage');
                                } else {
                                    $mode = is_dir($laravel_storage_public_path) ? 'J' : 'H';
                                    exec("mklink /{$mode} " . escapeshellarg(LARAVEL_PUBLIC_PATH . '/storage') . ' ' . escapeshellarg($laravel_storage_public_path));
                                }
                            } catch (Exception $e) {
                                $result['errors'][] = $e->getMessage();
                            }
                        } else {
                            $result['warnings'][] = "The [$laravel_storage_public_path] link already exists.";
                        }

                    } catch (PDOException $e) {
                        $result['errors'][] = $e->getMessage();
                        unlink(ENV_PATH);
                        $step = 'install';
                    }

                } else {
                    $result['errors'][] = 'Failed copy file: [' . ENV_EXAMPLE_PATH . ']';
                    unlink(ENV_PATH);
                    $step = 'install';
                }
            }
        }

        if (!file_exists(ENV_PATH) && empty($result['errors'])) {
            $step = 'verification';
        }
    }
?>

<html lang="en">
<head>
    <title>Installer</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700" rel='stylesheet' type='text/css'>
</head>

<body class="bg-light">

    <div class="container">
        <div class="pt-5 text-center">
            <i style="font-size: 43px">🚀</i>
            <h2>Installation</h2>
        </div>
        <div class="row">
            <div class="col-xl-8 offset-xl-2">

                <?php
                // STEP: verification
                 if ($step === 'verification') {
                     include __DIR__ . '/alerts.php';
                 ?>
                     <div class="text-center">
                         <h5>Server Requirements</h5>
                     </div>
                     <table class="table table-striped">
                         <thead>
                             <tr>
                                 <td><strong>Requirement</strong></td>
                                 <td><strong>Result</strong></td>
                             </tr>
                         </thead>
                         <tbody>
                         <tr>
                             <td>PHP Version >= 7.3</td>
                             <td>
                                 <?php
                                 if (version_compare(phpversion(), '7.3.10', '<')) {
                                     echo 'Error ';
                                 }
                                 echo(PHP_VERSION);
                                 ?>
                             </td>
                         </tr>
                         <tr>
                             <td>BCMath PHP Extension</td>
                             <td><?php echo extension_loaded('BCMath') ? 'OK' : 'Error'; ?></td>
                         </tr>
                         <tr>
                             <td>Ctype PHP Extension</td>
                             <td><?php echo extension_loaded('Ctype') ? 'OK' : 'Error'; ?></td>
                         </tr>
                         <tr>
                             <td>Fileinfo PHP Extension</td>
                             <td><?php echo extension_loaded('Fileinfo') ? 'OK' : 'Error'; ?></td>
                         </tr>
                         <tr>
                             <td>JSON PHP Extension</td>
                             <td><?php echo extension_loaded('JSON') ? 'OK' : 'Error'; ?></td>
                         </tr>
                         <tr>
                             <td>Mbstring PHP Extension</td>
                             <td><?php echo extension_loaded('Mbstring') ? 'OK' : 'Error'; ?></td>
                         </tr>
                         <tr>
                             <td>OpenSSL PHP Extension</td>
                             <td><?php echo extension_loaded('OpenSSL') ? 'OK' : 'Error'; ?></td>
                         </tr>
                         <tr>
                             <td>PDO PHP Extension</td>
                             <td><?php echo extension_loaded('PDO') ? 'OK' : 'Error'; ?></td>
                         </tr>
                         <tr>
                             <td>Tokenizer PHP Extension</td>
                             <td><?php echo extension_loaded('Tokenizer') ? 'OK' : 'Error'; ?></td>
                         </tr>
                         <tr>
                             <td>XML PHP Extension</td>
                             <td><?php echo extension_loaded('XML') ? 'OK' : 'Error'; ?></td>
                         </tr>

                         </tbody>
                     </table>

                     <div class="mt-5 pb-5 text-center">
                         <a href="<?php echo $url . '?step=install' ?>" class="w-100 btn btn-lg btn-primary">Go to install</a>
                     </div>

                 <?php
                 // STEP: install
                 } elseif ($step === 'install') {
                     include __DIR__ . '/alerts.php';
                 ?>
                     <div class="text-center">
                         <h5>Set parameters</h5>
                     </div>
                     <form method="POST" action="<?php echo $url . '?step=finish' ?>">
                         <div class="controls">

                             <!-- APP -->
                             <div class="row" hidden>
                                 <div class="col-md-12">
                                     <div class="form-group">
                                         <label for="app_name">Locale</label>
                                         <select name="app_locale" class="form-control">
                                             <option selected="selected" value="en">EN</option>
                                             <option value="ru">RU</option>
                                         </select>
                                         <div class="help-block with-errors"></div>
                                     </div>
                                 </div>
                             </div>

                             <div class="row" hidden>
                                 <div class="col-md-12">
                                     <div class="form-group">
                                         <label for="app_name">App URL</label>
                                         <input name="app_url" value="<?php echo $app_url;?>" type="url" class="form-control" required>
                                         <div class="help-block with-errors"></div>
                                     </div>
                                 </div>
                             </div>

                             <div class="row">
                                 <div class="col-md-12">
                                     <div class="form-group">
                                         <label for="app_name">App name</label>
                                         <input name="app_name" value="<?php echo $app_name;?>" type="text" class="form-control" required>
                                         <div class="help-block with-errors"></div>
                                     </div>
                                 </div>
                             </div>

                             <hr>
                             <!-- DATABASE -->
                             <div class="row">
                                 <div class="col-md-6">
                                     <div class="form-group">
                                         <label for="db_host">DB Host</label>
                                         <input name="db_host" value="<?php echo $db_host;?>" type="text" class="form-control" required>
                                         <div class="help-block with-errors"></div>
                                     </div>
                                 </div>
                                 <div class="col-md-6">
                                     <div class="form-group">
                                         <label for="db_port">DB Port</label>
                                         <input name="db_port" value="<?php echo $db_port;?>" type="text" class="form-control" required>
                                         <div class="help-block with-errors"></div>
                                     </div>
                                 </div>
                             </div>
                             <div class="row">
                                 <div class="col-md-6">
                                     <div class="form-group">
                                         <label for="db_database">DB Name</label>
                                         <input name="db_database" value="<?php echo $db_database;?>" type="text" class="form-control" required>
                                         <div class="help-block with-errors"></div>
                                     </div>
                                 </div>
                                 <div class="col-md-6">
                                     <div class="form-group">
                                         <label for="db_username">DB Username</label>
                                         <input name="db_username" value="<?php echo $db_username;?>" type="text" class="form-control" required>
                                         <div class="help-block with-errors"></div>
                                     </div>
                                 </div>
                             </div>
                             <div class="row">
                                 <div class="col-md-12">
                                     <div class="form-group">
                                         <label for="db_password">DB Password</label>
                                         <input name="db_password" value="<?php echo $db_password;?>" type="password" class="form-control" required>
                                         <div class="help-block with-errors"></div>
                                     </div>
                                 </div>
                             </div>
                             <hr>
                             <!-- DATABASE seed -->
                             <?php
                             if (count($sql_imports)) {
                             ?>
                             <div class="row">
                                 <div class="col-md-12">
                                     <div class="form-group">
                                         <label for="sql_import">Database content</label>
                                         <select name="sql_import" class="form-control">
                                             <?php
                                             foreach ($sql_imports as $dump) {
                                                 echo "<option value='{$dump}'>{$dump}</option>";
                                             }
                                             ?>
                                         </select>
                                         <div class="help-block with-errors"></div>
                                     </div>
                                 </div>
                             </div>

                             <?php
                             } else {
                                echo "<input type='hidden' name='sql_import' value=''>";
                             }
                             ?>

                             <div class="mt-5 pb-5 text-center">
                                 <button class="w-100 btn btn-lg btn-primary" type="submit">Save</button>
                             </div>
                         </div>
                     </form>
                <?php
                 // STEP: finish
                 } elseif ($step === 'finish') {
                     include __DIR__ . '/alerts.php';
                    ?>
                     <div class="text-center">
                         <h5>Finish</h5>
                     </div>
                    <table class="table table-striped">
                        <tbody>
                        <tr>
                            <td>Login</td>
                            <td>admin@app.com</td>
                        </tr>
                        <tr>
                            <td>Password</td>
                            <td>password</td>
                        </tr>
                        <tr>
                            <td>URL</td>
                            <td>
                                <a href="<?php echo $app_url . '/admin'?>">
                                    <?php echo $app_url . '/admin'?>
                                </a>
                            </td>
                        </tr>
                        </tbody>
                    </table>

                    <div class="mt-5 pb-5 text-center">
                        <a href="<?php echo $app_url ?>" class="w-100 btn btn-lg btn-primary">Visit site</a>
                    </div>
                    <?php
                // STEP: no step
                } else {
                     $result['errors'][] = 'Error step. Restart the installation';
                     include __DIR__ . '/alerts.php';

                 ?>
                    <div class="mt-5 pb-5 text-center">
                        <a href="<?php echo $app_url ?>" class="w-100 btn btn-lg btn-primary">Restart</a>
                    </div>
                <?php
                 }
                ?>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
</body>
</html>
