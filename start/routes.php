<?php

declare(strict_types=1);

use Meulah\Routing\Router;

return static function (Router $router): void {
    require dirname(__DIR__) . '/routes/web.php';
};
