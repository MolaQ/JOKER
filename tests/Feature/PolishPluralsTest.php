<?php

/**
 * trans_choice() odmienia według języka, w którym znalazł klucz, a w razie braku według języka zapasowego.
 * Teksty w kodzie są po polsku, więc zapasowy musi być polski („7 zespołów”, a nie „7 zespoły”).
 */
test('polish plural forms are picked for numbers above four', function () {
    app()->setLocale('pl');

    expect(trans_choice(':count zespół|:count zespoły|:count zespołów', 7, ['count' => 7]))->toBe('7 zespołów')
        ->and(trans_choice(':count spotkanie|:count spotkania|:count spotkań', 21, ['count' => 21]))->toBe('21 spotkań')
        ->and(trans_choice(':count spotkanie|:count spotkania|:count spotkań', 22, ['count' => 22]))->toBe('22 spotkania')
        ->and(trans_choice(':count mecz|:count mecze|:count meczów', 5, ['count' => 5]))->toBe('5 meczów');
});

test('english translations keep english plural rules', function () {
    app()->setLocale('en');

    expect(trans_choice('Trener|Trenerzy|Trenerzy', 2))->toBe('Coaches')
        ->and(__('Sztab'))->toBe('Staff');
});
