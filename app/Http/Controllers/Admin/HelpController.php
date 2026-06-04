<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class HelpController extends Controller
{
    public function __invoke(): View
    {
        return $this->guide('user-guide.html', 'Administrator Help Guide');
    }

    public function teacher(): View
    {
        return $this->guide('teacher-guide.html', 'Teacher Help Guide');
    }

    public function student(): View
    {
        return $this->guide('student-guide.html', 'Student Help Guide');
    }

    public function parent(): View
    {
        return $this->guide('parent-guide.html', 'Parent Help Guide');
    }

    private function guide(string $filename, string $title): View
    {
        $path = resource_path("views/help/guides/{$filename}");

        abort_unless(File::exists($path), 404);

        $html = File::get($path);
        $body = $this->extractBody($html);

        return view('help.guide', [
            'title' => __($title),
            'guideBody' => $body,
        ]);
    }

    private function extractBody(string $html): string
    {
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $matches)) {
            return trim($matches[1]);
        }

        return $html;
    }
}
