<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CtnDocument;
use App\Models\ToolFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class DocumentService
{
    private const CTN_DOCUMENTS_PATH = 'documents';
    private const TOOL_PATH = 'tools';

    public function storeCtnDocument(UploadedFile $file, int $id, string $fileName, string $typ): void
    {
        $path = $this->filePathForActor(
            self::CTN_DOCUMENTS_PATH,
            (string)$id
        );

        $file->storeAs(
            $path,
            $file->getClientOriginalName()
        );

        $this->persistCtnDocumentEntity(
            $path,
            $id,
            $fileName,
            $typ,
            $file
        );

        Log::info('Stored document: ' . $path);
    }

    public function storeToolDocument(UploadedFile $file, int $toolId, string $type): void
    {
        $path = $this->filePathForActor(self::TOOL_PATH, (string) $toolId);

        $file->storeAs(
            $path,
            $file->getClientOriginalName()
        );

        $this->persistToolFileEntity(
            $file,
            $type,
            $toolId
        );

        Log::info('Stored tool file: ' . $path);
    }

    private function filePathForActor(string $path, string $id): string
    {
        return $path . '/' . $id;
    }

    private function persistCtnDocumentEntity(string $path, int $id, string $name, string $typ, UploadedFile $file): void
    {
        CtnDocument::create(
            $name,
            $typ,
            $this->fullFilePath($path, $file),
            $id,
            $file->getClientOriginalName()
        )->save();
    }

    private function persistToolFileEntity(UploadedFile $file, string $type, int $toolId): void
    {
        ToolFile::create([
            'type' => $type,
            'filename' => $file->getClientOriginalName(),
            'tool_id' => $toolId,
        ]);
    }

    private function fullFilePath(string $path, UploadedFile $file): string
    {
        return $path . '/' . $file->getClientOriginalName();
    }
}
