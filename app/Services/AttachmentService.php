<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class AttachmentService
{
    public function replace(Model $owner, UploadedFile $file, User $uploader, string $directory): Attachment
    {
        $owner->attachments()->get()->each->delete();

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
