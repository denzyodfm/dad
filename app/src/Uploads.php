<?php
declare(strict_types=1);

namespace App;

/**
 * Handles cover pictures and audio/video attachments.
 *
 * Files are accepted on what they contain, not what they are called: the type
 * is read from the bytes and mapped to an extension we choose. The uploaded
 * name is never used, so a file called "shell.php.jpg" cannot land as .php.
 */
final class Uploads
{
    public const MAX_IMAGE_BYTES = 8 * 1024 * 1024;
    public const MAX_MEDIA_BYTES = 60 * 1024 * 1024;

    private const IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    private const MEDIA_TYPES = [
        'audio/mpeg' => ['mp3', 'audio'],
        'audio/wav' => ['wav', 'audio'],
        'audio/x-wav' => ['wav', 'audio'],
        'audio/ogg' => ['ogg', 'audio'],
        'audio/mp4' => ['m4a', 'audio'],
        'audio/webm' => ['weba', 'audio'],
        'video/mp4' => ['mp4', 'video'],
        'video/webm' => ['webm', 'video'],
        'video/ogg' => ['ogv', 'video'],
        'video/quicktime' => ['mov', 'video'],
    ];

    public function __construct(private string $directory)
    {
    }

    public function directory(): string
    {
        return $this->directory;
    }

    /**
     * Stores a cover picture.
     *
     * @param array|null $file One entry from $_FILES.
     * @return string|null The stored file name, or null when nothing was sent.
     */
    public function storeImage(?array $file): ?string
    {
        if (!$this->wasUploaded($file)) {
            return null;
        }
        $this->assertSize($file, self::MAX_IMAGE_BYTES, 'Pictures must be 8 MB or smaller.');
        $mime = $this->detect($file['tmp_name']);
        if (!isset(self::IMAGE_TYPES[$mime])) {
            throw new ValidationException('Pictures must be JPG, PNG, WebP or GIF.');
        }
        // getimagesize fails on anything that is not really an image.
        if (@getimagesize($file['tmp_name']) === false) {
            throw new ValidationException('That picture could not be read.');
        }
        return $this->place($file['tmp_name'], self::IMAGE_TYPES[$mime]);
    }

    /**
     * Stores an audio or video attachment.
     *
     * @return array{0:string,1:string}|null [file name, 'audio'|'video'], or null.
     */
    public function storeMedia(?array $file): ?array
    {
        if (!$this->wasUploaded($file)) {
            return null;
        }
        $this->assertSize($file, self::MAX_MEDIA_BYTES, 'Recordings must be 60 MB or smaller.');
        $mime = $this->detect($file['tmp_name']);
        if (!isset(self::MEDIA_TYPES[$mime])) {
            throw new ValidationException('Recordings must be MP3, WAV, OGG, M4A, MP4, WebM or MOV.');
        }
        [$extension, $kind] = self::MEDIA_TYPES[$mime];
        return [$this->place($file['tmp_name'], $extension), $kind];
    }

    /** Removes a stored file. Silently ignores names that are not ours. */
    public function delete(?string $name): void
    {
        if ($name === null || $name === '' || !$this->isOwnName($name)) {
            return;
        }
        $path = $this->directory . '/' . $name;
        if (is_file($path)) {
            unlink($path);
        }
    }

    /** True when the browser actually sent a file for this field. */
    public function wasUploaded(?array $file): bool
    {
        if ($file === null || !isset($file['error'])) {
            return false;
        }
        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return false;
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new ValidationException($this->describeError((int) $file['error']));
        }
        return is_uploaded_file($file['tmp_name']) || PHP_SAPI === 'cli';
    }

    private function assertSize(array $file, int $limit, string $message): void
    {
        if ((int) ($file['size'] ?? 0) > $limit) {
            throw new ValidationException($message);
        }
    }

    private function detect(string $path): string
    {
        $info = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $info->file($path);
        return is_string($mime) ? $mime : 'application/octet-stream';
    }

    /** Writes the file under a random name we control. */
    private function place(string $tmp, string $extension): string
    {
        if (!is_dir($this->directory) && !mkdir($this->directory, 0775, true) && !is_dir($this->directory)) {
            throw new ValidationException('The upload directory could not be created.');
        }
        $name = bin2hex(random_bytes(16)) . '.' . $extension;
        $target = $this->directory . '/' . $name;
        $moved = PHP_SAPI === 'cli' ? rename($tmp, $target) : move_uploaded_file($tmp, $target);
        if (!$moved) {
            throw new ValidationException('The file could not be saved.');
        }
        @chmod($target, 0644);
        return $name;
    }

    private function isOwnName(string $name): bool
    {
        return preg_match('/^[a-f0-9]{32}\.[a-z0-9]{2,4}$/', $name) === 1;
    }

    private function describeError(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'That file is larger than the server allows.',
            UPLOAD_ERR_PARTIAL => 'The upload did not finish. Try again.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'The server could not store the file.',
            UPLOAD_ERR_EXTENSION => 'The server refused that file.',
            default => 'That file could not be uploaded.',
        };
    }
}
