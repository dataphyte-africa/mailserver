<?php

namespace App\Http\Controllers\CP\Platform;

use App\Models\Organisation;
use App\Services\Forms\ScopedProductFormProductSelector;
use App\Services\Platform\OrganisationNewsletterDomainService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrganisationDomainController
{
    public function __construct(
        private readonly ScopedProductFormProductSelector $organisations,
        private readonly OrganisationNewsletterDomainService $domains,
    ) {}

    public function index(Request $request): View
    {
        $organisations = $this->organisations->organisationsFor($request->user());

        return view('platform.cp.organisation-domains.index', [
            'organisations' => $organisations,
            'domains' => $this->domains,
        ]);
    }

    public function update(Request $request, Organisation $organisation): RedirectResponse
    {
        abort_if(
            ! $this->organisations->resolveOrganisation($request->user(), (int) $organisation->getKey()),
            403,
            'Organisation is outside your active scope.'
        );

        $validated = $request->validate([
            'source_domain' => ['nullable', 'string', 'max:255'],
            'newsletter_subdomain' => ['nullable', 'string', 'max:63'],
            'newsletter_dns_record_type' => ['nullable', Rule::in(['A', 'CNAME'])],
            'newsletter_dns_expected_value' => ['nullable', 'string', 'max:255'],
        ]);

        $this->domains->update($organisation, $validated);

        return redirect()
            ->back()
            ->with('success', "{$organisation->name} newsletter domain updated.");
    }

    public function verify(Request $request, Organisation $organisation): RedirectResponse
    {
        abort_if(
            ! $this->organisations->resolveOrganisation($request->user(), (int) $organisation->getKey()),
            403,
            'Organisation is outside your active scope.'
        );

        $result = $this->domains->verify($organisation);

        return redirect()
            ->back()
            ->with($result['matched'] ? 'success' : 'warning', $result['message']);
    }
}
