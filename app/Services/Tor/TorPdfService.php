<?php

namespace App\Services\Tor;

use App\Models\TorInvitation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class TorPdfService
{
    public function generate(TorInvitation $invitation): string
    {
        $invitation->loadMissing('flow.organisation');
        $pdf = Pdf::loadView('tor.pdf.signed', ['invitation' => $invitation])->setPaper('a4');
        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        $organisation = $invitation->flow->organisation;
        $firstOffice = $organisation->publicAddresses()[0] ?? [];
        $address = trim((string) ($firstOffice['address'] ?? ''));
        $logoPath = null;
        if (filled($organisation->logo_path) && Storage::disk('public')->exists($organisation->logo_path)) {
            // Dompdf needs a readable filesystem path. Do not depend on the
            // public storage symlink being present on the production server.
            $logoPath = Storage::disk('public')->path($organisation->logo_path);
        }

        $dompdf->getCanvas()->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($organisation, $address, $logoPath): void {
            $font = $fontMetrics->getFont('DejaVu Sans', 'normal');
            $brandColor = [0.25, 0.3, 0.38];

            if ($logoPath && is_file($logoPath)) {
                $canvas->image($logoPath, 45, 24, 132, 31);
            } else {
                $canvas->text(45, 37, $organisation->name, $font, 13, $brandColor);
            }

            $lines = array_filter(explode("\n", wordwrap($address, 48)));
            foreach (array_values($lines) as $index => $line) {
                $width = $fontMetrics->getTextWidth($line, $font, 8.5);
                $canvas->text(550 - $width, 30 + ($index * 12), $line, $font, 8.5, $brandColor);
            }

            $canvas->line(45, 72, 550, 72, [0.84, 0.86, 0.89], 0.75);
        });

        $contents = $dompdf->output();
        $path = 'tor-signed/'.$invitation->flow->organisation_id.'/'.$invitation->id.'-'.$invitation->verification_code.'.pdf';
        Storage::disk('local')->put($path, $contents);

        return $path;
    }
}
