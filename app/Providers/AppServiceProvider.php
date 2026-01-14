<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

   public function boot(): void
{
    $routeBase = base_path('routes');

    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($routeBase, \FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $fileName = $file->getFilename();

        // Skip Laravel defaults
        if (in_array($fileName, ['web.php', 'api.php', 'console.php', 'channels.php'], true)) {
            continue;
        }

        // Path parts relative to /routes (e.g. "SuperAdmin/GlobalSetup")
        $absPath     = $file->getPathname();
        $relative    = str_replace($routeBase . DIRECTORY_SEPARATOR, '', $absPath);
        $dirRelative = trim(str_replace($fileName, '', $relative), DIRECTORY_SEPARATOR);

        $urlPrefix  = '';
        $namePrefix = '';

        if ($dirRelative !== '') {
            $segments   = array_map(fn ($s) => strtolower($s), explode(DIRECTORY_SEPARATOR, $dirRelative));
            $urlPrefix  = implode('/', $segments);       // superadmin/globalsetup
            $namePrefix = implode('.', $segments) . '.'; // superadmin.globalsetup.
        }

        // Build the registrar and conditionally add prefix/as
        $registrar = \Illuminate\Support\Facades\Route::middleware('web');
        if ($urlPrefix !== '') {
            $registrar = $registrar->prefix($urlPrefix);
        }
        if ($namePrefix !== '') {
            $registrar = $registrar->as($namePrefix);
        }

        $registrar->group($absPath);
    }
}

}
