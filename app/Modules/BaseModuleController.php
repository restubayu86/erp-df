<?php

namespace App\Modules;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\HTTP\Exceptions\RedirectException;
use Psr\Log\LoggerInterface;

/**
 * Dasar semua controller modul (Dyeing, Finishing, ...).
 *
 * View dicari di app/Modules/{Modul}/Views/ (khusus modul) lebih dulu,
 * lalu di app/Modules/Core/Views/ (dipakai bersama).
 */
abstract class BaseModuleController extends BaseController
{
    /** Segmen URL & nama folder modul, mis. 'dyeing'. */
    protected string $moduleName;

    /** Nilai kolom machines.type / schedules.division untuk modul ini. */
    protected string $moduleType;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        // Pembatasan divisi (PRD 8.3): user harus punya division.{modul}; admin otomatis punya semuanya.
        $user = auth()->user();
        if (! $user || ! $user->can('division.' . $this->moduleType)) {
            throw new RedirectException(
                redirect()->to(site_url('/'))->with('error', 'Anda tidak punya akses ke bagian ' . ucfirst($this->moduleType) . '.')
            );
        }
    }

    /** @return list<array<string,mixed>> */
    protected function getMachines(): array
    {
        return db_connect()->table('machines')
            ->where('type', $this->moduleType)
            ->where('is_active', 1)
            ->orderBy('code')
            ->get()
            ->getResultArray();
    }

    protected function modulePage(string $view, array $data = [], string $title = '', array $breadcrumbs = [], string $actions = ''): string
    {
        $data['moduleType'] = $this->moduleType;
        $data['moduleName'] = $this->moduleName;

        return $this->page($this->resolveView($view), $data, $title, $breadcrumbs, $actions);
    }

    /** Nama view ber-namespace: milik modul bila ada, jika tidak milik Core. */
    private function resolveView(string $view): string
    {
        $name = str_replace('/', '\\', $view);
        $own  = 'App\\Modules\\' . ucfirst($this->moduleName) . '\\Views\\' . $name;

        return service('locator')->locateFile($own, 'Views') !== false
            ? $own
            : 'App\\Modules\\Core\\Views\\' . $name;
    }
}
