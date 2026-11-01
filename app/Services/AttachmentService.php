<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class AttachmentService
{
    public function add(Model $owner, UploadedFile $file, User $uploader, string $directory): Attachment
    {
        return $owner->attachments()->create([
            'uploaded_by' => $uploader->id,
            'disk' => 'local',
            'path' => $file->store($directory, 'local'),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);
    }
}
