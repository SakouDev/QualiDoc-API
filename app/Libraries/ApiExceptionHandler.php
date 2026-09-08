<?php

namespace App\Libraries;

use CodeIgniter\Debug\ExceptionHandlerInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * Cette API ne sert jamais de HTML : toute exception non attrapée (JSON
 * malformé, requête inattendue...) doit ressortir dans le même format que
 * le reste des erreurs de l'API, jamais une stack trace brute qui fuite
 * les chemins du serveur.
 */
class ApiExceptionHandler implements ExceptionHandlerInterface
{
    public function handle(Throwable $exception, RequestInterface $request, ResponseInterface $response, int $statusCode, int $exitCode): void
    {
        if ($statusCode < 400 || $statusCode > 599) {
            $statusCode = 500;
        }

        $message = ($statusCode === 500 && ENVIRONMENT === 'production')
            ? 'Une erreur interne est survenue.'
            : $exception->getMessage();

        $response
            ->setStatusCode($statusCode)
            ->setJSON(['status' => $statusCode, 'error' => $statusCode, 'messages' => ['error' => $message]])
            ->send();

        exit($exitCode);
    }
}
