<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class PdfRenderer
{
    /** Lagre til disk (legacy) og returnér relativ sti under storage/app */
    public function renderHtmlToPdf(string $html, ?string $baseUrl = null, array $cssUrls = []): string
    {
        $url = config('services.weasy.url');

        if (!$url) {
            // Fallback: lagre HTML hvis ingen renderer er satt
            $path = 'reports/'.date('Y/m').'/report_'.uniqid().'.html';
            Storage::disk('local')->put($path, $html);
            return $path;
        }

        $resp = Http::timeout(30)->post(rtrim($url,'/').'/render', [
            'html'     => $html,
            'base_url' => $baseUrl,
            'css_urls' => $cssUrls,
        ]);

        if (!$resp->ok()) {
            $path = 'reports/'.date('Y/m').'/report_'.uniqid().'.html';
            Storage::disk('local')->put($path, $html);
            return $path;
        }

        $pdf  = $resp->body();
        $path = 'reports/'.date('Y/m').'/report_'.uniqid().'.pdf';
        Storage::disk('local')->put($path, $pdf);
        return $path;
    }

    /** NY: returner bytes direkte (ingen mellomlagring på disk) */
    public function renderBytes(string $html, ?string $baseUrl = null, array $cssUrls = []): array
    {
        $url = config('services.weasy.url');

        if (!$url) {
            // Fallback: lever HTML direkte
            return ['bytes' => $html, 'mime' => 'text/html', 'filename' => 'report.html'];
        }

        $resp = Http::timeout(30)->post(rtrim($url,'/').'/render', [
            'html'     => $html,
            'base_url' => $baseUrl,
            'css_urls' => $cssUrls,
        ]);

        if (!$resp->ok()) {
            return ['bytes' => $html, 'mime' => 'text/html', 'filename' => 'report.html'];
        }

        return ['bytes' => $resp->body(), 'mime' => 'application/pdf', 'filename' => 'report.pdf'];
    }
}
