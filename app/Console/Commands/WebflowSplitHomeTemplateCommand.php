<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class WebflowSplitHomeTemplateCommand extends Command
{
    protected $signature = 'webflow:split-home-template {--force : Overwrite existing partials}';

    protected $description = 'Split mirror home template into start/header/main/footer/end partials and separate CSS section.';

    public function handle(): int
    {
        $sourcePath = resource_path('views/webflow/mirror/home.blade.php');
        if (! File::exists($sourcePath)) {
            $this->error('Source template not found: '.$sourcePath);

            return self::FAILURE;
        }

        $raw = File::get($sourcePath);
        $html = $this->extractHtml($raw);

        $headerStart = strpos($html, '<div class="header-container-2">');
        $mainStart = strpos($html, '<div class="div-block-59">');
        $footerStart = strpos($html, '<footer');
        $footerEnd = strrpos($html, '</footer>');

        if ($headerStart === false || $mainStart === false || $footerStart === false || $footerEnd === false) {
            $this->error('Could not find expected header/main/footer markers in mirror template.');

            return self::FAILURE;
        }

        $footerEnd += strlen('</footer>');

        $start = substr($html, 0, $headerStart);
        $header = substr($html, $headerStart, $mainStart - $headerStart);
        $main = substr($html, $mainStart, $footerStart - $mainStart);
        $footer = substr($html, $footerStart, $footerEnd - $footerStart);
        $end = substr($html, $footerEnd);

        $cssSection = $this->extractCssSection($start);

        $partialsDir = resource_path('views/webflow/mirror/partials');
        File::ensureDirectoryExists($partialsDir);

        $this->writeFile("{$partialsDir}/home-start.blade.php", $start);
        $this->writeFile("{$partialsDir}/home-header.blade.php", $header);
        $this->writeFile("{$partialsDir}/home-main.blade.php", $main);
        $this->writeFile("{$partialsDir}/home-footer.blade.php", $footer);
        $this->writeFile("{$partialsDir}/home-end.blade.php", $end);
        $this->writeFile("{$partialsDir}/home-css.blade.php", $cssSection !== '' ? $cssSection : '<!-- No CSS block extracted -->');

        $newHome = <<<'BLADE'
@include('webflow.mirror.partials.home-start')
@include('webflow.mirror.partials.home-header')
@include('webflow.mirror.partials.home-main')
@include('webflow.mirror.partials.home-footer')
@include('webflow.mirror.partials.home-end')
BLADE;

        File::put($sourcePath, $newHome.PHP_EOL);

        $this->info('Home template split successfully.');
        $this->line('- Updated: resources/views/webflow/mirror/home.blade.php');
        $this->line('- Created: resources/views/webflow/mirror/partials/home-start.blade.php');
        $this->line('- Created: resources/views/webflow/mirror/partials/home-header.blade.php');
        $this->line('- Created: resources/views/webflow/mirror/partials/home-main.blade.php');
        $this->line('- Created: resources/views/webflow/mirror/partials/home-footer.blade.php');
        $this->line('- Created: resources/views/webflow/mirror/partials/home-end.blade.php');
        $this->line('- Created: resources/views/webflow/mirror/partials/home-css.blade.php');

        return self::SUCCESS;
    }

    private function extractHtml(string $content): string
    {
        if (preg_match("/echo <<<'HTML'\\R(.*)\\RHTML;\\R@endphp/s", $content, $matches) === 1) {
            return $matches[1];
        }

        return $content;
    }

    private function extractCssSection(string $startHtml): string
    {
        if (preg_match('/(<link[^>]+webflow\\.shared[^>]+>.*?)(<\\/head>)/si', $startHtml, $matches) !== 1) {
            return '';
        }

        return trim($matches[1]);
    }

    private function writeFile(string $path, string $content): void
    {
        if (File::exists($path) && ! $this->option('force')) {
            return;
        }

        File::put($path, $content);
    }
}

