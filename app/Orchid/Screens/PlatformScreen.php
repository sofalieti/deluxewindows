<?php

declare(strict_types=1);

namespace App\Orchid\Screens;

use App\Services\HeroVariantService;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Screen;
use Orchid\Support\Color;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class PlatformScreen extends Screen
{
    public function __construct(
        private readonly HeroVariantService $hero,
    ) {}

    /**
     * Fetch data to be displayed on the screen.
     *
     * @return array
     */
    public function query(): iterable
    {
        return [
            'hero' => [
                'is_new' => $this->hero->variant() === 'new',
            ],
        ];
    }

    /**
     * The name of the screen displayed in the header.
     */
    public function name(): ?string
    {
        return 'Dashboard';
    }

    /**
     * Display header description.
     */
    public function description(): ?string
    {
        return $this->hero->variant() === 'new'
            ? 'The site is serving the new hero block.'
            : 'The site is serving the old promo hero block.';
    }

    /**
     * The screen's action buttons.
     *
     * @return \Orchid\Screen\Action[]
     */
    public function commandBar(): iterable
    {
        return [];
    }

    /**
     * The screen's layout elements.
     *
     * @return \Orchid\Screen\Layout[]
     */
    public function layout(): iterable
    {
        return [
            Layout::view('platform::partials.update-assets'),

            Layout::rows([
                Switcher::make('hero.is_new')
                    ->sendTrueOrFalse()
                    ->title('Hero block')
                    ->placeholder('Use the new hero block')
                    ->help('On — new hero: consultation-first layout, sticky bottom CTA, no discount badge. Off — old promo hero with the % OFF badge and round estimate button. Applies to the homepage, city landing pages and county hubs at once.'),

                Button::make('Save hero block')
                    ->method('saveHeroVariant')
                    ->type(Color::PRIMARY)
                    ->icon('bs.check-lg'),
            ])
                ->title('Hero block (site-wide)'),

            Layout::view('admin.dashboard.hero-preview'),

            Layout::view('platform::partials.welcome'),
        ];
    }

    public function saveHeroVariant(Request $request): void
    {
        $variant = $this->hero->update($request->boolean('hero.is_new') ? 'new' : 'old');

        Toast::info($variant === 'new'
            ? 'The site now serves the new hero block.'
            : 'The site now serves the old promo hero block.');
    }
}
