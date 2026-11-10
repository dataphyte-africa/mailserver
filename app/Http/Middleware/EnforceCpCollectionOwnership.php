<?php

namespace App\Http\Middleware;

use App\Services\Platform\CpOperatorScope;
use App\Services\Platform\NewsletterAssetContainers;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceCpCollectionOwnership
{
    public function __construct(
        private readonly CpOperatorScope $scope,
        private readonly NewsletterAssetContainers $assetContainers,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->scope->isSuper($request->user())) {
            return $next($request);
        }

        if ($request->is('cp/assets-fieldtype')) {
            $ids = $request->input('assets', []);
            abort_unless($request->isMethod('POST') && is_array($ids)
                && collect($ids)->every(function ($id) use ($request) {
                    if (! is_string($id) || ! str_contains($id, '::')) {
                        return false;
                    }
                    return $this->assetContainers->canUse($request->user(), explode('::', $id, 2)[0]);
                }), 403);
            return $next($request);
        }

        if ($request->is('cp/assets') && $request->isMethod('POST')) {
            abort_unless($this->assetContainers->canUse($request->user(), (string) $request->input('container')), 403);
            return $next($request);
        }

        if ($request->isMethod('GET') && preg_match('~^cp/assets/browse/(?:folders|search)/([^/]+)(?:/.*)?$~', $request->path(), $matches)) {
            abort_unless($this->assetContainers->canUse($request->user(), $matches[1]), 403);
            return $next($request);
        }

        foreach (['forms', 'assets', 'assets-fieldtype', 'blueprints', 'globals', 'navigation', 'taxonomies', 'users', 'roles', 'user-groups', 'permissions', 'search'] as $path) {
            abort_if($request->is("cp/{$path}") || $request->is("cp/{$path}/*"), 403);
        }

        if (! $request->is('cp/collections/*')) {
            return $next($request);
        }

        abort_if($request->is('cp/collections/actions*') || $request->is('cp/collections/*/actions*'), 403);

        $collection = $request->route('collection');
        if ($collection !== null) {
            $handle = is_string($collection) ? $collection : $collection->handle();
            abort_unless($this->scope->canCollection($request->user(), $handle), 403);
        }

        return $next($request);
    }
}
