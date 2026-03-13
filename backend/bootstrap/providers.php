<?php

use App\Providers\AppServiceProvider;
use App\Providers\ResendMailServiceProvider;
use App\Providers\SqliteConfigServiceProvider;

return [
    AppServiceProvider::class,
    ResendMailServiceProvider::class,
    SqliteConfigServiceProvider::class,
];
