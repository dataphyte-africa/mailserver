<?php

namespace App\Http\Middleware;

use App\Services\Platform\CpOperatorScope;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceCpCollectionOwnership
{
    public function __construct(private readonly CpOperatorScope $scope) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->scope->isSuper($request->user())) {
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
