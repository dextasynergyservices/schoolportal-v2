<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Validates that an uploaded file is a genuine CSV — not a disguised
 * executable, PHP script, or binary file that could harm the server.
 */
class SafeCsvFile implements ValidationRule
{
    /**
     * Dangerous patterns that should never appear in a CSV file.
     * Covers PHP shells, script injections, and executable signatures.
     */
    private const DANGEROUS_PATTERNS = [
        '<?php',
        '<?=',
        '<script',
        '#!/',
        'eval(',
        'base64_decode(',
        'exec(',
        'system(',
        'passthru(',
        'shell_exec(',
        'popen(',
        'proc_open(',
        '<?xml',
    ];

    /**
     * Binary file signatures (magic bytes) that indicate non-text files.
     * Each entry is [hex_signature, description].
     */
    private const BINARY_SIGNATURES = [
        "\x50\x4B\x03\x04" => 'ZIP/DOCX/XLSX archive',
        "\x25\x50\x44\x46" => 'PDF document',
        "\x7F\x45\x4C\x46" => 'ELF executable',
        "\x4D\x5A" => 'Windows executable',
        "\x89\x50\x4E\x47" => 'PNG image',
        "\xFF\xD8\xFF" => 'JPEG image',
        "\x47\x49\x46\x38" => 'GIF image',
        "\x52\x61\x72\x21" => 'RAR archive',
        "\x1F\x8B" => 'GZIP archive',
        "\xD0\xCF\x11\xE0" => 'MS Office (old format)',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            $fail(__('The :attribute must be an uploaded file.'));

            return;
        }

        $path = $value->getRealPath();

        if ($path === false || ! is_readable($path)) {
            $fail(__('The :attribute could not be read.'));

            return;
        }

        // Read enough to check signatures and scan content
        $content = file_get_contents($path, false, null, 0, 8192);

        if ($content === false || $content === '') {
            $fail(__('The :attribute is empty or unreadable.'));

            return;
        }

        // 1. Check for binary file signatures (magic bytes)
        foreach (self::BINARY_SIGNATURES as $signature => $description) {
            if (str_starts_with($content, $signature)) {
                $fail(__('The :attribute appears to be a :type, not a CSV file.', ['type' => $description]));

                return;
            }
        }

        // 2. Check for high ratio of non-printable characters (binary content)
        $nonPrintable = 0;
        $total = strlen($content);
        for ($i = 0; $i < $total; $i++) {
            $ord = ord($content[$i]);
            // Allow: tab (9), newline (10), carriage return (13), printable ASCII (32-126), and common UTF-8 (128-255)
            if ($ord < 9 || ($ord > 13 && $ord < 32) || $ord === 127) {
                $nonPrintable++;
            }
        }

        if ($total > 0 && ($nonPrintable / $total) > 0.05) {
            $fail(__('The :attribute contains binary content and is not a valid CSV file.'));

            return;
        }

        // 3. Check for dangerous code patterns (case-insensitive)
        $lowerContent = strtolower($content);
        foreach (self::DANGEROUS_PATTERNS as $pattern) {
            if (str_contains($lowerContent, strtolower($pattern))) {
                $fail(__('The :attribute contains potentially dangerous content and was rejected.'));

                return;
            }
        }

        // 4. Verify the file has at least one comma or tab (basic CSV structure check)
        $firstLine = strtok($content, "\n");
        if ($firstLine !== false && ! str_contains($firstLine, ',') && ! str_contains($firstLine, "\t")) {
            $fail(__('The :attribute does not appear to be a valid CSV file (no delimiters found in header).'));

            return;
        }
    }
}
