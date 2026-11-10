<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\GenerateSignedUploadUrl;

it('zapisuje przyczynę nieudanego wysłania pliku do osobnego logu', function () {
    $log = storage_path('logs/wysylanie-plikow.log');
    File::delete($log);

    $this->post(route('livewire.upload-file'), ['files' => [UploadedFile::fake()->image('zdjecie.jpg')]])
        ->assertStatus(401);

    expect(File::exists($log))->toBeTrue()
        ->and(File::get($log))->toContain('"kod":401')->toContain('"podpis_ok":false')->toContain('zdjecie.jpg');

    File::delete($log);
});

it('nie zapisuje nic, gdy plik dotarł', function () {
    $log = storage_path('logs/wysylanie-plikow.log');
    File::delete($log);

    Storage::fake('tmp-for-tests');
    $url = (new GenerateSignedUploadUrl)->forLocal();

    $this->post($url, ['files' => [UploadedFile::fake()->image('zdjecie.jpg')]])->assertOk();

    expect(File::exists($log))->toBeFalse();
});
