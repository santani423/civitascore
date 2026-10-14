<?php

use Modules\Admission\Support\QrCode;

/**
 * Referensi dibangkitkan oleh pustaka qrcode.react (Nayuki qrcodegen, ECC M,
 * tanpa margin) di frontend — port PHP harus identik modul per modul.
 */
it('produces module matrices identical to the frontend reference implementation', function () {
    $reference = json_decode((string) file_get_contents(__DIR__.'/../Fixtures/qr_reference.json'), true);

    expect($reference)->not->toBeEmpty();

    foreach ($reference as $text => $expected) {
        $matrix = QrCode::matrix((string) $text, 'M');
        $actual = implode("\n", array_map(fn ($row) => implode('', array_map(fn ($dark) => $dark ? '1' : '0', $row)), $matrix));

        expect($actual)->toBe($expected, 'QR untuk teks sepanjang '.strlen((string) $text).' karakter berbeda');
    }
});

it('renders a PNG data URI', function () {
    expect(QrCode::pngDataUri('https://example.test/x'))->toStartWith('data:image/png;base64,');
});
