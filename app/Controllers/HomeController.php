<?php

declare(strict_types=1);

namespace App\Controllers;

use Meulah\Config\Repository;
use Meulah\Http\Response;
use Meulah\Http\ResponseInterface;
use Meulah\View\View;

final class HomeController
{
    public function __construct(
        private readonly View $views,
        private readonly Repository $config,
    ) {
    }

    public function __invoke(): ResponseInterface
    {
        return Response::html($this->views->render('home', [
            'applicationName' => $this->config->string('app.name'),
        ]));
    }
}
