<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class WebflowNormalizeMirrorTemplatesCommand extends Command
{
    protected $signature = 'webflow:normalize-mirror-templates {--force : Overwrite generated partials}';

    protected $description = 'Deduplicate mirror templates by extracting shared header/footer/css/js partials.';

    public function handle(): int
    {
        $mirrorDir = resource_path('views/webflow/mirror');
        $homePath = $mirrorDir.'/home.blade.php';

        if (! File::exists($homePath)) {
            $this->error('Home mirror template not found: '.$homePath);

            return self::FAILURE;
        }

        $homeHtml = $this->extractHtml(File::get($homePath));
        $headerStart = strpos($homeHtml, '<div class="header-container-2">');
        $mainStart = strpos($homeHtml, '<div class="div-block-59">');
        $footerStart = strpos($homeHtml, '<footer');
        $footerEnd = strrpos($homeHtml, '</footer>');

        if ($headerStart === false || $mainStart === false || $footerStart === false || $footerEnd === false) {
            $this->error('Could not detect header/main/footer boundaries in home mirror template.');

            return self::FAILURE;
        }

        $footerEnd += strlen('</footer>');
        $sharedHeader = substr($homeHtml, $headerStart, $mainStart - $headerStart);
        $sharedFooter = substr($homeHtml, $footerStart, $footerEnd - $footerStart);
        $sharedCss = $this->extractSharedCssBlock($homeHtml);
        $sharedJs = $this->extractSharedJsBlock($homeHtml);

        $partialsRoot = $mirrorDir.'/partials';
        $pagesPartialsRoot = $partialsRoot.'/pages';
        File::ensureDirectoryExists($partialsRoot);
        File::ensureDirectoryExists($pagesPartialsRoot);

        $this->writeIfAllowed($partialsRoot.'/shared-header.blade.php', $sharedHeader);
        $this->writeIfAllowed($partialsRoot.'/shared-footer.blade.php', $sharedFooter);
        $this->writeIfAllowed($partialsRoot.'/shared-css.blade.php', $sharedCss !== '' ? $sharedCss : '<!-- shared css not detected -->');
        $this->writeIfAllowed($partialsRoot.'/shared-js.blade.php', $sharedJs !== '' ? $sharedJs : '<!-- shared js not detected -->');

        $files = collect(File::allFiles($mirrorDir))
            ->filter(fn (\SplFileInfo $file) => $file->getExtension() === 'php')
            ->filter(fn (\SplFileInfo $file) => str_ends_with($file->getFilename(), '.blade.php'))
            ->filter(fn (\SplFileInfo $file) => ! Str::contains($file->getPathname(), $partialsRoot))
            ->values();

        $updated = 0;
        $skipped = 0;

        foreach ($files as $file) {
            $path = $file->getPathname();
            $relative = str_replace('\\', '/', substr($path, strlen($mirrorDir) + 1));
            $relativeNoExt = Str::before($relative, '.blade.php');
            $viewSlug = str_replace('/', '.', $relativeNoExt);

            $html = $this->extractHtml(File::get($path));
            $hStart = strpos($html, '<div class="header-container-2">');
            $fStart = strpos($html, '<footer');
            $fEnd = strrpos($html, '</footer>');

            if ($hStart === false || $fStart === false || $fEnd === false) {
                $skipped++;
                continue;
            }

            $fEnd += strlen('</footer>');
            $hEnd = $fStart;
            $possibleHeader = substr($html, $hStart, $fStart - $hStart);
            if (($positionInHeader = strpos($possibleHeader, '<div class="div-block-59">')) !== false) {
                $hEnd = $hStart + $positionInHeader;
            } elseif (($marker = strpos($possibleHeader, '</style></div></div>')) !== false) {
                $hEnd = $hStart + $marker + strlen('</style></div></div>');
            }

            $start = substr($html, 0, $hStart);
            $main = substr($html, $hEnd, $fStart - $hEnd);
            $end = substr($html, $fEnd);

            $start = $this->replaceSharedCssWithInclude($start);
            $end = $this->replaceSharedJsWithInclude($end);

            $pagePartialDir = $pagesPartialsRoot.'/'.str_replace('/', DIRECTORY_SEPARATOR, $relativeNoExt);
            File::ensureDirectoryExists($pagePartialDir);

            $this->writeIfAllowed($pagePartialDir.'/start.blade.php', $start);
            $this->writeIfAllowed($pagePartialDir.'/main.blade.php', $main);
            $this->writeIfAllowed($pagePartialDir.'/end.blade.php', $end);

            $newTopLevel = <<<BLADE
@include('webflow.mirror.partials.pages.{$viewSlug}.start')
@include('webflow.mirror.partials.shared-header')
@include('webflow.mirror.partials.pages.{$viewSlug}.main')
@include('webflow.mirror.partials.shared-footer')
@include('webflow.mirror.partials.pages.{$viewSlug}.end')
BLADE;

            File::put($path, $newTopLevel.PHP_EOL);
            $updated++;
        }

        $this->info("Mirror templates normalized. Updated: {$updated}, skipped: {$skipped}");
        $this->line('Shared partials:');
        $this->line('- resources/views/webflow/mirror/partials/shared-header.blade.php');
        $this->line('- resources/views/webflow/mirror/partials/shared-footer.blade.php');
        $this->line('- resources/views/webflow/mirror/partials/shared-css.blade.php');
        $this->line('- resources/views/webflow/mirror/partials/shared-js.blade.php');

        return self::SUCCESS;
    }

    private function extractHtml(string $content): string
    {
        if (preg_match("/echo <<<'HTML'\\R(.*)\\RHTML;\\R@endphp/s", $content, $matches) === 1) {
            return $matches[1];
        }

        return $content;
    }

    private function extractSharedCssBlock(string $html): string
    {
        $pattern = '/<link href="https:\/\/cdn\.prod\.website-files\.com\/[^"]+webflow\.shared[^"]+"[^>]*>.*?<style>\s*\.w-webflow-badge\s*\{[^}]+\}\s*<\/style>/si';
        if (preg_match($pattern, $html, $matches) !== 1) {
            return '';
        }

        return trim($matches[0]);
    }

    private function extractSharedJsBlock(string $html): string
    {
        $pattern = '/<script src="https:\/\/d3e54v103j8qbb\.cloudfront\.net\/js\/jquery-3\.5\.1[^>]*><\/script>\s*<script src="https:\/\/cdn\.prod\.website-files\.com\/[^"]+\/js\/webflow\.schunk\.[^"]+"[^>]*><\/script>\s*<script src="https:\/\/cdn\.prod\.website-files\.com\/[^"]+\/js\/webflow\.schunk\.[^"]+"[^>]*><\/script>\s*<script src="https:\/\/cdn\.prod\.website-files\.com\/[^"]+\/js\/webflow\.[^"]+"[^>]*><\/script>/si';
        if (preg_match($pattern, $html, $matches) !== 1) {
            return '';
        }

        return trim($matches[0]);
    }

    private function replaceSharedCssWithInclude(string $start): string
    {
        $pattern = '/<link href="https:\/\/cdn\.prod\.website-files\.com\/[^"]+webflow\.shared[^"]+"[^>]*>.*?<style>\s*\.w-webflow-badge\s*\{[^}]+\}\s*<\/style>/si';

        return preg_replace(
            $pattern,
            "@include('webflow.mirror.partials.shared-css')",
            $start,
            1
        ) ?? $start;
    }

    private function replaceSharedJsWithInclude(string $end): string
    {
        $pattern = '/<script src="https:\/\/d3e54v103j8qbb\.cloudfront\.net\/js\/jquery-3\.5\.1[^>]*><\/script>\s*<script src="https:\/\/cdn\.prod\.website-files\.com\/[^"]+\/js\/webflow\.schunk\.[^"]+"[^>]*><\/script>\s*<script src="https:\/\/cdn\.prod\.website-files\.com\/[^"]+\/js\/webflow\.schunk\.[^"]+"[^>]*><\/script>\s*<script src="https:\/\/cdn\.prod\.website-files\.com\/[^"]+\/js\/webflow\.[^"]+"[^>]*><\/script>/si';

        return preg_replace(
            $pattern,
            "@include('webflow.mirror.partials.shared-js')",
            $end,
            1
        ) ?? $end;
    }

    private function writeIfAllowed(string $path, string $content): void
    {
        if (File::exists($path) && ! $this->option('force')) {
            return;
        }

        File::put($path, $content);
    }
}

