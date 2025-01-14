<?php

namespace App\Http\Controllers;

use App\LaravelVersionFromPath;
use App\Models\LaravelVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class LaravelVersionsController extends Controller
{
    public function index()
    {
        $versions = Cache::remember('laravel-versions', 3600, function () {
            return LaravelVersion::orderBy('major', 'desc')->orderBy('minor', 'desc')->get();
        });

        $activeVersions = $versions->filter(function ($version) {
            return $version->released_at->endOfDay()->gt(now())
                || ($version->ends_securityfixes_at && $version->ends_securityfixes_at->endOfDay()->gt(now()));
        });

        $inActiveVersions = $versions->filter(function ($version) {
            return $version->released_at->endOfDay()->lt(now()) &&
                (! $version->ends_securityfixes_at || $version->ends_securityfixes_at->endOfDay()->lt(now()));
        });

        return view('versions.index', [
            'activeVersions' => $activeVersions,
            'inactiveVersions' => $inActiveVersions,
        ]);
    }

    public function show($path): View
    {
        [$version, $sanitizedPath, $segments] = (new LaravelVersionFromPath)($path);

        return view('versions.show', [
            'version' => $version,
            'path' => $sanitizedPath,
            'segments' => $segments,
        ]);
    }
}
