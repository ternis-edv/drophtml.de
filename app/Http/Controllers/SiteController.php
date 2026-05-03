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
        
        if (empty($path)) {
            // Try to find index.html
            if (Storage::disk('public')->exists("{$site->path}/index.html")) {
                $path = 'index.html';
            } else {
                // Find the first .html file
                $files = Storage::disk('public')->files($site->path);
                $htmlFiles = array_filter($files, fn($f) => str_ends_with(strtolower($f), '.html'));
                
                if (!empty($htmlFiles)) {
                    $path = basename(reset($htmlFiles));
                } else {
                    abort(404, 'No HTML file found.');
                }
            }
        }

        $fullPath = "{$site->path}/{$path}";

        if (!Storage::disk('public')->exists($fullPath)) {
            // If it's a directory request
            if (Storage::disk('public')->exists("{$fullPath}/index.html")) {
                return redirect("/s/{$slug}/{$path}/index.html");
            }
            abort(404);
        }

        $file = Storage::disk('public')->get($fullPath);
        $mime = Storage::disk('public')->mimeType($fullPath);

        // Ensure we serve .html files as text/html even if mimeType fails
        if (str_ends_with(strtolower($path), '.html')) {
            $mime = 'text/html';
        }

        return response($file, 200)->header('Content-Type', $mime);
    }
}
