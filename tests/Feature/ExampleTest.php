<?php

declare(strict_types=1);

use Madgeek\Msg91\Msg91;

it('resolves the singleton', function () {
    expect(app(Msg91::class))->toBeInstanceOf(Msg91::class);
});

it('returns the same instance from the container', function () {
    expect(app(Msg91::class))->toBe(app(Msg91::class));
});

it('loads the package translations', function () {
    expect(trans('laravel-msg91::messages.placeholder'))->toBe('Msg91 placeholder translation.');
});

it('loads the package views', function () {
    expect(view()->exists('laravel-msg91::placeholder'))->toBeTrue();
});
