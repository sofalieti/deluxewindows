<?php

test('the homepage path chooser is present only as Bing-only markup', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('who-paths bing-only')
        ->toContain('Who Are You?')
        ->toContain('href="/for/homeowners"')
        ->toContain('href="/for/hoas"')
        ->toContain('href="/for/property-managers"')
        ->toContain('href="/for/designers-architects"')
        ->toContain('href="/for/builders"')
        ->toContain('Write your own')
        ->toContain('data-open-who-own')
        ->toContain('Who: Write your own')
        ->toContain('class="bing-hide"');
});

test('an audience page is noindex and sends other visitors home', function () {
    $html = $this->get('/for/homeowners')->assertOk()->getContent();

    expect($html)
        ->toContain('name="robots" content="noindex,nofollow"')
        ->toContain("location.replace('/')")
        ->toContain('Windows and doors for the home you love')
        ->toContain('Who: Homeowners')
        ->toContain('data-audience-form')
        ->toContain('What is bothering you most?')
        ->toContain('audience-homeowners-form.jpg');

    $this->get('/for/not-a-path')->assertNotFound();
});
