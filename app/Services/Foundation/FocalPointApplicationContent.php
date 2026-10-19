<?php

namespace App\Services\Foundation;

use App\Services\Forms\ApplicationWindow;
use Carbon\CarbonImmutable;
use Statamic\Facades\GlobalSet;

class FocalPointApplicationContent
{
    public function __construct(private readonly ApplicationWindow $window) {}

    public function data(): array
    {
        return GlobalSet::findByHandle('focal_point_application')?->inDefaultSite()?->data()?->toArray() ?? [];
    }

    public function heroIntro(): string
    {
        return (string) ($this->data()['hero_intro'] ?? "Help coordinate election observers across Nigeria for Dataphyte Foundation's 2027 election observation exercise.");
    }

    public function intro(): string
    {
        return (string) ($this->data()['intro'] ?? "State Focal Points will recruit, coordinate, supervise and train observers in their state, and report to the Foundation's focal person. Applicants must reside in the state they apply to coordinate.");
    }

    public function selectionNote(): string
    {
        return (string) ($this->data()['selection_note'] ?? 'Submitting an application does not guarantee selection. We will contact applicants about their status after the deadline.');
    }

    public function preparationNote(): string
    {
        return (string) ($this->data()['preparation_note'] ?? 'Prepare your CV (maximum two pages) and highest-qualification certificate before you begin.');
    }

    public function closesAt(): CarbonImmutable
    {
        return $this->window->closesAt((string) ($this->data()['closes_at'] ?? config('focal_point_applications.closes_at')));
    }

    public function isOpen(): bool
    {
        $data = $this->data();

        return $this->window->isOpen((bool) ($data['accepting_submissions'] ?? true),
            (string) ($data['closes_at'] ?? config('focal_point_applications.closes_at')));
    }

    public function torHtml(): string
    {
        $html = (string) ($this->data()['tor_html'] ?? view('foundation.focal-points._terms')->render());
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', 'h2,h3,p,ul,ol,li,strong,em,u,blockquote,a[href],br');

        return (new \HTMLPurifier($config))->purify($html);
    }

    public function torVersion(): string
    {
        return 'sha256:'.hash('sha256', $this->torHtml());
    }

    public function torApproved(): bool
    {
        return ($this->data()['approved_tor_version'] ?? null) === $this->torVersion();
    }

    public function canApprove(): bool
    {
        $html = $this->torHtml();

        return filled(strip_tags($html));
    }
}
