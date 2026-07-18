<?php

use Modules\FileManagement\Models\FileUpload;
use Modules\FileManagement\Support\NullVirusScanner;

test('the null virus scanner always reports a file as clean', function () {
    $upload = FileUpload::factory()->make();

    expect((new NullVirusScanner)->scan($upload))->toBeTrue();
});
