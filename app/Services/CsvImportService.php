<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SchoolClass;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CsvImportService
{
    /**
     * Parse a CSV file and return validated rows with errors.
     *
     * @return array{rows: array, errors: array, valid_count: int, error_count: int}
     */
    public function parseCsv(string $filePath, int $schoolId): array
    {
        $rows = [];
        $errors = [];
        $lineNumber = 0;

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return ['rows' => [], 'errors' => [['line' => 0, 'message' => 'Could not open file.']], 'valid_count' => 0, 'error_count' => 1];
        }

        // Read header row
        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);

            return ['rows' => [], 'errors' => [['line' => 0, 'message' => 'Empty CSV file.']], 'valid_count' => 0, 'error_count' => 1];
        }

        // Normalize header names (trim, lowercase)
        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $header);

        // Required columns
        $requiredColumns = ['name', 'username', 'gender', 'class'];
        $missingColumns = array_diff($requiredColumns, $header);

        if (! empty($missingColumns)) {
            fclose($handle);

            return [
                'rows' => [],
                'errors' => [['line' => 1, 'message' => 'Missing required columns: '.implode(', ', $missingColumns)]],
                'valid_count' => 0,
                'error_count' => 1,
            ];
        }

        // Pre-load classes and levels for lookup
        $classes = SchoolClass::where('is_active', true)->get()->keyBy(fn ($c) => strtolower($c->name));
        $existingUsernames = User::pluck('username')->map(fn ($u) => strtolower($u))->toArray();
        $seenUsernames = [];

        while (($data = fgetcsv($handle)) !== false) {
            $lineNumber++;
            $rowNumber = $lineNumber + 1; // +1 for header row

            // Skip empty rows
            if (count(array_filter($data)) === 0) {
                continue;
            }

            // Map columns to data
            $row = [];
            foreach ($header as $i => $col) {
                $row[$col] = trim($data[$i] ?? '');
            }

            // Validate row
            $rowErrors = $this->validateRow($row, $rowNumber, $classes, $existingUsernames, $seenUsernames);

            if (! empty($rowErrors)) {
                foreach ($rowErrors as $error) {
                    $errors[] = $error;
                }
                $row['_valid'] = false;
            } else {
                $row['_valid'] = true;
                $seenUsernames[] = strtolower($row['username']);

                // Resolve class
                $class = $classes[strtolower($row['class'])] ?? null;
                $row['_class_id'] = $class?->id;
                $row['_level_id'] = $class?->level_id;
            }

            $row['_line'] = $rowNumber;
            $rows[] = $row;
        }

        fclose($handle);

        $validCount = count(array_filter($rows, fn ($r) => $r['_valid']));

        return [
            'rows' => $rows,
            'errors' => $errors,
            'valid_count' => $validCount,
            'error_count' => count($rows) - $validCount,
        ];
    }

    /**
     * Validate a single CSV row.
     */
    private function validateRow(array $row, int $lineNumber, $classes, array $existingUsernames, array $seenUsernames): array
    {
        $errors = [];

        if (empty($row['name'])) {
            $errors[] = ['line' => $lineNumber, 'message' => 'Name is required.'];
        }

        if (empty($row['username'])) {
            $errors[] = ['line' => $lineNumber, 'message' => 'Username is required.'];
        } elseif (in_array(strtolower($row['username']), $existingUsernames)) {
            $errors[] = ['line' => $lineNumber, 'message' => "Username \"{$row['username']}\" already exists."];
        } elseif (in_array(strtolower($row['username']), $seenUsernames)) {
            $errors[] = ['line' => $lineNumber, 'message' => "Duplicate username \"{$row['username']}\" in CSV."];
        }

        if (empty($row['gender']) || ! in_array(strtolower($row['gender']), ['male', 'female', 'other'])) {
            $errors[] = ['line' => $lineNumber, 'message' => 'Gender must be male, female, or other.'];
        }

        if (empty($row['class'])) {
            $errors[] = ['line' => $lineNumber, 'message' => 'Class is required.'];
        } elseif (! $classes->has(strtolower($row['class']))) {
            $errors[] = ['line' => $lineNumber, 'message' => "Class \"{$row['class']}\" not found."];
        }

        return $errors;
    }

    /**
     * Import validated rows into the database.
     *
     * @return int Number of students imported
     */
    public function importRows(array $rows, string $defaultPassword): int
    {
        $imported = 0;
        $sessionId = app()->bound('current.school') && $school = app('current.school')
            ? $school->currentSession()?->id
            : null;

        DB::transaction(function () use ($rows, $defaultPassword, $sessionId, &$imported) {
            foreach ($rows as $row) {
                if (! ($row['_valid'] ?? false)) {
                    continue;
                }

                $user = User::create([
                    'name' => $row['name'],
                    'username' => $row['username'],
                    'password' => Hash::make($defaultPassword),
                    'role' => 'student',
                    'gender' => strtolower($row['gender']),
                    'level_id' => $row['_level_id'],
                    'must_change_password' => true,
                ]);

                StudentProfile::create([
                    'user_id' => $user->id,
                    'school_id' => $user->school_id,
                    'class_id' => $row['_class_id'],
                    'admission_number' => $row['admission_number'] ?? null,
                    'date_of_birth' => ! empty($row['date_of_birth']) ? $row['date_of_birth'] : null,
                    'address' => $row['address'] ?? null,
                    'blood_group' => $row['blood_group'] ?? null,
                    'enrolled_session_id' => $sessionId,
                ]);

                $imported++;
            }
        });

        return $imported;
    }
}
