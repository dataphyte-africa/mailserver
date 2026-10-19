<?php

namespace App\Services\Foundation;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleDriveApplicationStorage
{
    public function configured(): bool
    {
        $config = config('focal_point_applications.google_drive');

        return filled($config['client_id']) && filled($config['client_secret'])
            && filled($config['refresh_token']) && filled($config['cv_folder_id'])
            && filled($config['certificate_folder_id']);
    }

    /** @return array{id: string, url: string, filename: string} */
    public function upload(UploadedFile $file, string $kind): array
    {
        if (! $this->configured() || ! in_array($kind, ['cv', 'certificate'], true)) {
            throw new RuntimeException('Application document storage is not configured.');
        }

        $folder = config("focal_point_applications.google_drive.{$kind}_folder_id");
        $filename = $file->getClientOriginalName();
        $metadata = json_encode([
            'name' => $filename,
            'parents' => [$folder],
        ], JSON_THROW_ON_ERROR);
        $boundary = 'focal-point-'.bin2hex(random_bytes(16));
        $body = "--{$boundary}\r\nContent-Type: application/json; charset=UTF-8\r\n\r\n{$metadata}\r\n";
        $body .= "--{$boundary}\r\nContent-Type: {$file->getMimeType()}\r\n\r\n";
        $body .= file_get_contents($file->getRealPath());
        $body .= "\r\n--{$boundary}--\r\n";

        $response = Http::withToken($this->accessToken())
            ->timeout(45)
            ->withBody($body, "multipart/related; boundary={$boundary}")
            ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,webViewLink');

        if (! $response->successful() || ! is_string($response->json('id'))) {
            throw new RuntimeException('Google Drive did not accept the application document.');
        }

        $id = $response->json('id');

        return [
            'id' => $id,
            'url' => $response->json('webViewLink') ?: "https://drive.google.com/file/d/{$id}/view",
            'filename' => $filename,
        ];
    }

    public function delete(string $fileId): void
    {
        if ($fileId === '' || ! $this->configured()) {
            return;
        }

        Http::withToken($this->accessToken())->timeout(15)
            ->delete('https://www.googleapis.com/drive/v3/files/'.rawurlencode($fileId));
    }

    private function accessToken(): string
    {
        return Cache::remember('focal_point_applications.google_drive.access_token', 3000, function (): string {
            $response = Http::asForm()->timeout(15)->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('focal_point_applications.google_drive.client_id'),
                'client_secret' => config('focal_point_applications.google_drive.client_secret'),
                'refresh_token' => config('focal_point_applications.google_drive.refresh_token'),
                'grant_type' => 'refresh_token',
            ]);

            if (! $response->successful() || ! is_string($response->json('access_token'))) {
                throw new RuntimeException('Google Drive authentication is unavailable.');
            }

            return $response->json('access_token');
        });
    }
}
