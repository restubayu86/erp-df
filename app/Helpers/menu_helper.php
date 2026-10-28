<?php
// Dimuat di BaseController: protected $helpers = [..., 'menu'];

if (! function_exists('app_name')) {
    function app_name(): string
    {
        return config('Menu')->appName;
    }
}

if (! function_exists('user_modules')) {
    /** Kode modul (dyeing / finishing) tempat user bekerja. Admin = semua. */
    function user_modules($user = null): array
    {
        $user ??= auth()->user();
        if (! $user) {
            return [];
        }
        $out = [];
        foreach (config('Menu')->modules as $code => $m) {
            if ($user->can($m['perm'])) {
                $out[] = $code;
            }
        }
        return $out;
    }
}

if (! function_exists('current_module')) {
    /** Modul yang sedang dibuka: ?module= (jika boleh) -> terakhir dipakai (session) -> modul pertama user. */
    function current_module(): ?string
    {
        $allowed = user_modules();
        if (! $allowed) {
            return null;
        }
        $asked = service('request')->getGet('module');
        if (is_string($asked) && in_array($asked, $allowed, true)) {
            session()->set('erp_module', $asked);
            return $asked;
        }
        $saved = session('erp_module');
        return in_array($saved, $allowed, true) ? $saved : $allowed[0];
    }
}

if (! function_exists('menu_path_active')) {
    function menu_path_active(string $path): bool
    {
        $cur  = trim(uri_string(), '/');
        $path = trim($path, '/');
        return $path === '' ? $cur === '' : ($cur === $path || str_starts_with($cur, $path . '/'));
    }
}

if (! function_exists('menu_active')) {
    /** Dipertahankan untuk view lama: cocokkan awal URL (tanpa query). */
    function menu_active(string $url): bool
    {
        return menu_path_active(strtok($url, '?'));
    }
}

if (! function_exists('menu_items')) {
    /**
     * Menu navbar untuk user saat ini, sudah difilter permission & bagian kerja.
     * @return list<array{label:string,icon:string,active:bool,url?:string,ready?:bool,children?:list<array>}>
     */
    function menu_items(): array
    {
        $user = auth()->user();
        if (! $user) {
            return [];
        }

        $cfg     = config('Menu');
        $allow   = static fn (?string $perm) => $perm === null || $user->can($perm);
        $current = current_module();
        $out     = [];

        foreach ($cfg->items as $item) {
            // Menu modul: satu dropdown per bagian yang boleh diakses user
            if (($item['type'] ?? '') === 'modules') {
                foreach (user_modules($user) as $code) {
                    $m        = $cfg->modules[$code];
                    $children = [];
                    foreach ($cfg->moduleItems as $ci) {
                        if (! $allow($ci['perm'] ?? null)) {
                            continue;
                        }
                        $children[] = [
                            'label'  => $ci['label'],
                            'icon'   => $ci['icon'],
                            'url'    => str_replace('{module}', $code, $ci['url']),
                            'ready'  => $ci['ready'] ?? true,
                            'active' => $current === $code && menu_path_active($ci['path']),
                        ];
                    }
                    if ($children) {
                        $out[] = [
                            'label'    => $m['label'],
                            'icon'     => $m['icon'],
                            'children' => $children,
                            'active'   => (bool) array_filter($children, static fn ($c) => $c['active']),
                        ];
                    }
                }
                continue;
            }

            if (! $allow($item['perm'] ?? null)) {
                continue;
            }

            if (! empty($item['children'])) {
                $children = [];
                foreach ($item['children'] as $c) {
                    if (! $allow($c['perm'] ?? null)) {
                        continue;
                    }
                    $children[] = [
                        'label'  => $c['label'],
                        'icon'   => $c['icon'] ?? 'chevron-right',
                        'url'    => $c['url'],
                        'ready'  => $c['ready'] ?? true,
                        'active' => menu_path_active($c['path'] ?? $c['url']),
                    ];
                }
                if ($children) {
                    $out[] = [
                        'label'    => $item['label'],
                        'icon'     => $item['icon'] ?? 'menu',
                        'children' => $children,
                        'active'   => (bool) array_filter($children, static fn ($c) => $c['active']),
                    ];
                }
                continue;
            }

            $out[] = [
                'label'  => $item['label'],
                'icon'   => $item['icon'] ?? 'circle',
                'url'    => $item['url'],
                'ready'  => $item['ready'] ?? true,
                'active' => menu_path_active($item['path'] ?? $item['url']),
            ];
        }
        return $out;
    }
}

if (! function_exists('user_display_name')) {
    function user_display_name($user = null): string
    {
        $user ??= auth()->user();
        return (string) ($user->username ?? $user?->email ?? 'Pengguna');
    }
}

if (! function_exists('user_initials')) {
    function user_initials(string $name): string
    {
        $parts = preg_split('/[\s._-]+/', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $ini   = '';
        foreach (array_slice($parts, 0, 2) as $p) {
            $ini .= mb_strtoupper(mb_substr($p, 0, 1));
        }
        return $ini !== '' ? $ini : '?';
    }
}

if (! function_exists('user_role_titles')) {
    /** Nama role (grup Shield) user, mis. ['Scheduler']. */
    function user_role_titles($user = null): array
    {
        $user ??= auth()->user();
        if (! $user) {
            return [];
        }
        $groups = config('AuthGroups')->groups;
        return array_map(static fn ($g) => $groups[$g]['title'] ?? ucfirst($g), $user->getGroups());
    }
}

if (! function_exists('nav_notifications')) {
    /** Notifikasi "perlu tindakan" untuk user saat ini (dihitung dari data schedule). */
    function nav_notifications(): array
    {
        static $cache = null;
        return $cache ??= (new \App\Libraries\NavNotifications())->forUser(auth()->user());
    }
}
