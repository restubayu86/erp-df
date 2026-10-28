<?php
namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class TvToken implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $expected = env('tv.token');
        $given    = $request->getGet('key') ?? $request->getHeaderLine('X-TV-Key');

        if (! $expected || ! hash_equals($expected, (string) $given)) {
            return service('response')->setStatusCode(403)->setBody('Forbidden');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
