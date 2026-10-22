<?php

namespace App\Support;

/**
 * Limits for files attached to assignments and submissions. The <x-file-picker>
 * component checks the same limits in the browser before upload.
 */
final class Upload
{
    public const EXTENSIONS = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

    public const MAX_MB = 10;

    /**
     * @return list<string>
     */
    public static function rules(): array
    {
        return ['file', 'mimes:'.implode(',', self::EXTENSIONS), 'max:'.self::MAX_MB * 1024];
    }
}
