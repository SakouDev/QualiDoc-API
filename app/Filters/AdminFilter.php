<?php

namespace App\Filters;

use App\Libraries\AuthContext;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Must run after the jwt filter (AuthContext already populated).
 * Rejects non-admin patients with 403.
 */
class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! AuthContext::isAdmin()) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON(['status' => 403, 'error' => 403, 'messages' => ['error' => 'Accès réservé aux administrateurs.']]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do after the request.
    }
}
