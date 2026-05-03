<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class SiteController extends Controller
{
    public function show(string $slug, ?string $path = null)
    {
        $site = Site::where('slug', $slug)->firstOrFail();
        
        if ($site->status !== 'active') {
            abort(403, 'This site is currently inactive or not deployed.');
        }

        // Determine actual file path first
        $requestedPath = $path;
        if (empty($requestedPath)) {
            // Try to find index.html
            if (Storage::disk('public')->exists("{$site->path}/index.html")) {
                $requestedPath = 'index.html';
            } else {
                // Find the first .html file
                $files = Storage::disk('public')->files($site->path);
                $htmlFiles = array_filter($files, fn($f) => str_ends_with(strtolower($f), '.html'));
                
                if (!empty($htmlFiles)) {
                    $requestedPath = basename(reset($htmlFiles));
                } else {
                    abort(404, 'No HTML file found.');
                }
            }
        }

        $fullPath = "{$site->path}/{$requestedPath}";

        if (!Storage::disk('public')->exists($fullPath)) {
            // If it's a directory request
            if (Storage::disk('public')->exists("{$fullPath}/index.html")) {
                return redirect("/s/{$slug}/{$requestedPath}/index.html");
            }
            abort(404);
        }

        $isHtml = str_ends_with(strtolower($requestedPath), '.html');

        // Detailed view tracking
        $site->siteViews()->create([
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referer' => request()->header('referer'),
            'is_quiet' => !$isHtml,
        ]);

        if ($isHtml) {
            $site->increment('views');
        }

        $file = Storage::disk('public')->get($fullPath);
        $mime = Storage::disk('public')->mimeType($fullPath);

        // Ensure we serve .html files as text/html even if mimeType fails
        if ($isHtml) {
            $mime = 'text/html';
            
            // Inject <base> tag for proper asset loading on path-based URLs
            $baseUrl = url("/s/{$slug}/") . '/';
            $baseTag = "<base href=\"{$baseUrl}\">";
            
            if (str_contains($file, '<head>')) {
                $file = str_replace('<head>', "<head>\n    {$baseTag}", $file);
            } else {
                $file = "{$baseTag}\n" . $file;
            }
        }

        return response($file, 200)->header('Content-Type', $mime);
    }
}
