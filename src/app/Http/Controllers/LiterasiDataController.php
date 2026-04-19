<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;

class LiterasiDataController extends Controller
{
    private string $basePath;

    private array $categories = [
        '01-fondasi' => ['title' => 'Fondasi', 'icon' => 'cube', 'color' => 'pandora-accent'],
        '02-data-engineering' => ['title' => 'Data Engineering', 'icon' => 'cog', 'color' => 'pandora-primary'],
        '03-klasifikasi' => ['title' => 'Klasifikasi', 'icon' => 'tag', 'color' => 'pandora-success'],
        '04-estimasi-regresi' => ['title' => 'Estimasi & Regresi', 'icon' => 'trending-up', 'color' => 'pandora-gold'],
        '05-clustering' => ['title' => 'Clustering', 'icon' => 'collection', 'color' => 'pandora-danger'],
        '06-association-rule' => ['title' => 'Association Rule', 'icon' => 'link', 'color' => 'pandora-accent'],
        '07-data-tak-terstruktur' => ['title' => 'Data Tak Terstruktur', 'icon' => 'photograph', 'color' => 'pandora-primary'],
    ];

    public function __construct()
    {
        $this->basePath = base_path('literasi-data');
    }

    /**
     * GET /literasi-data — daftar semua kategori.
     */
    public function index()
    {
        $categories = [];

        foreach ($this->categories as $slug => $meta) {
            $readmePath = $this->basePath . '/' . $slug . '/README.md';
            $description = '';
            $concepts = [];

            if (file_exists($readmePath)) {
                $content = file_get_contents($readmePath);

                // Extract description (first line after title)
                if (preg_match('/^#.*\n+(.+)/m', $content, $m)) {
                    $description = trim($m[1]);
                }

                // Count concepts (numbered list items with links)
                preg_match_all('/^\d+\.\s+\[(.+?)\]\((.+?)\)/m', $content, $matches);
                $concepts = count($matches[0]);
            }

            $categories[] = [
                'slug' => $slug,
                'title' => $meta['title'],
                'icon' => $meta['icon'],
                'color' => $meta['color'],
                'description' => $description,
                'concept_count' => $concepts,
            ];
        }

        return view('literasi-data.index', compact('categories'));
    }

    /**
     * GET /literasi-data/{category} — daftar konsep dalam kategori.
     */
    public function category(string $category)
    {
        abort_unless(isset($this->categories[$category]), 404);

        $readmePath = $this->basePath . '/' . $category . '/README.md';
        abort_unless(file_exists($readmePath), 404);

        $content = file_get_contents($readmePath);
        $meta = $this->categories[$category];

        // Parse concepts from README
        $concepts = [];
        preg_match_all('/^\d+\.\s+\[(.+?)\]\((.+?)\)/m', $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $i => $match) {
            $filename = $match[2];
            $slug = str_replace('.md', '', $filename);
            $concepts[] = [
                'number' => $i + 1,
                'title' => $match[1],
                'slug' => $slug,
                'filename' => $filename,
            ];
        }

        // Extract description
        $description = '';
        if (preg_match('/^#.*\n+(.+)/m', $content, $m)) {
            $description = trim($m[1]);
        }

        return view('literasi-data.category', compact('category', 'meta', 'concepts', 'description'));
    }

    /**
     * GET /literasi-data/{category}/{concept} — halaman konsep.
     */
    public function show(string $category, string $concept)
    {
        abort_unless(isset($this->categories[$category]), 404);

        $filePath = $this->basePath . '/' . $category . '/' . $concept . '.md';
        abort_unless(file_exists($filePath), 404);

        $raw = file_get_contents($filePath);
        $meta = $this->categories[$category];

        // Extract title from first # heading
        $title = $concept;
        if (preg_match('/^#\s+(.+)/m', $raw, $m)) {
            $title = trim($m[1]);
        }

        // Extract level if present
        $level = null;
        if (preg_match('/\*\*Level:\*\*\s*(.+?)(?:\s*\||$)/m', $raw, $m)) {
            $level = trim($m[1]);
        }

        // Convert internal .md links to app routes
        $raw = preg_replace_callback(
            '/\[(.+?)\]\((\d{2}-.+?)\.md\)/',
            function ($m) use ($category) {
                return '[' . $m[1] . '](/literasi-data/' . $category . '/' . $m[2] . ')';
            },
            $raw,
        );

        // Convert cross-category links (../XX-category/file.md)
        $raw = preg_replace_callback(
            '/\[(.+?)\]\(\.\.\/(\d{2}-.+?)\/(\d{2}-.+?)\.md\)/',
            function ($m) {
                return '[' . $m[1] . '](/literasi-data/' . $m[2] . '/' . $m[3] . ')';
            },
            $raw,
        );

        $html = Str::markdown($raw, [
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);

        // Get prev/next concepts for navigation
        $readmePath = $this->basePath . '/' . $category . '/README.md';
        $nav = $this->getConceptNav($readmePath, $concept);

        return view('literasi-data.show', compact('category', 'meta', 'title', 'level', 'html', 'nav'));
    }

    private function getConceptNav(string $readmePath, string $currentSlug): array
    {
        $nav = ['prev' => null, 'next' => null];
        if (!file_exists($readmePath)) {
            return $nav;
        }

        $content = file_get_contents($readmePath);
        preg_match_all('/^\d+\.\s+\[(.+?)\]\((.+?)\)/m', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $i => $match) {
            $slug = str_replace('.md', '', $match[2]);
            if ($slug === $currentSlug) {
                if ($i > 0) {
                    $nav['prev'] = [
                        'title' => $matches[$i - 1][1],
                        'slug' => str_replace('.md', '', $matches[$i - 1][2]),
                    ];
                }
                if ($i < count($matches) - 1) {
                    $nav['next'] = [
                        'title' => $matches[$i + 1][1],
                        'slug' => str_replace('.md', '', $matches[$i + 1][2]),
                    ];
                }
                break;
            }
        }

        return $nav;
    }
}
