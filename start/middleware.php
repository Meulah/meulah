<?php

declare(strict_types=1);

use Meulah\Application;

return static function (Application $app): void {
    // Middleware runs in listed order for the request and reverse order for the response.
    //
    // $app->middleware(
    //     $first,
    //     $second,
    // );
};
