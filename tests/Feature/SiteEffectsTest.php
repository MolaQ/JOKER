<?php

use App\Support\SiteSettings;

test('efekty są domyślnie włączone i opisane klasami na stronie', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('class="fx-site fx-reveal fx-page-transitions fx-hover fx-images fx-counters fx-header"')
        ->and($html)->toContain('data-header-bar');
});

test('wyłączony efekt znika z klas strony', function () {
    app(SiteSettings::class)->set(['fx_reveal' => false, 'fx_page_transitions' => false]);

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('class="fx-site fx-hover fx-images fx-counters fx-header"');
});
