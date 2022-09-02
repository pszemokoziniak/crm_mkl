<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CtnDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class CtnDocumentService
{
    private const PATH = 'documents';

    public function store(UploadedFile $file, int $id, string $fileName, string $typ): void
    {
        $path = $this->filePathForActor((string) $id);

        $file->storeAs($path, $file->getClientOriginalName());

        $this->storeEntity($path, $id, $fileName, $typ, $file);

        Log::info('Stored document: ' . $path);
    }

    private function filePathForActor(string $id): string
    {
        return static::PATH . '/' . $id;
    }

    private function storeEntity(string $path, int $id, string $name, string $typ, UploadedFile $file): void
    {
        CtnDocument::create($name, $typ, $this->fullFilePath($path,$file), $id, $file->getClientOriginalName())->save();
    }

    private function fullFilePath(string $path, UploadedFile $file): string
    {
        return $path . '/' . $file->getClientOriginalName();
    }
}
