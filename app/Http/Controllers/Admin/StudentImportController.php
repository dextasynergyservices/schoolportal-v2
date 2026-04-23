<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Rules\SafeCsvFile;
use App\Services\CsvImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentImportController extends Controller
{
    public function __construct(
        private readonly CsvImportService $csvImportService,
    ) {}

    /**
     * Show the CSV upload form.
     */
    public function create(): View
    {
        return view('admin.students.import');
    }

    /**
     * Parse the uploaded CSV and show preview.
     */
    public function preview(Request $request): View|RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:2048', new SafeCsvFile],
            'default_password' => ['required', 'string', 'min:6'],
        ]);

        $file = $request->file('csv_file');
        $school = app('current.school');

        try {
            $result = $this->csvImportService->parseCsv($file->getRealPath(), $school->id);
        } catch (\Throwable $e) {
            return redirect()->route('admin.students.import')
                ->with('error', __('Failed to parse CSV file: :message', ['message' => $e->getMessage()]));
        }

        // Store file temporarily for the import step
        $tempPath = $file->storeAs('imports', 'students_'.time().'.csv', 'local');

        return view('admin.students.import-preview', [
            'rows' => $result['rows'],
            'errors' => $result['errors'],
            'validCount' => $result['valid_count'],
            'errorCount' => $result['error_count'],
            'tempPath' => $tempPath,
            'defaultPassword' => $request->input('default_password'),
        ]);
    }

    /**
     * Import the validated students.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'temp_path' => ['required', 'string'],
            'default_password' => ['required', 'string', 'min:6'],
        ]);

        $fullPath = storage_path('app/private/'.$request->input('temp_path'));

        if (! file_exists($fullPath)) {
            return redirect()->route('admin.students.import')
                ->with('error', __('Import file expired. Please upload again.'));
        }

        try {
            $school = app('current.school');
            $result = $this->csvImportService->parseCsv($fullPath, $school->id);
            $importResult = $this->csvImportService->importRows($result['rows'], $request->input('default_password'));
        } catch (\Throwable $e) {
            @unlink($fullPath);

            return redirect()->route('admin.students.import')
                ->with('error', __('Import failed: :message', ['message' => $e->getMessage()]));
        }

        // Clean up temp file
        @unlink($fullPath);

        $imported = $importResult['imported'];
        $skipped = $importResult['skipped'];

        if ($imported === 0 && count($skipped) > 0) {
            // Nothing imported — all skipped
            return redirect()->route('admin.students.import')
                ->with('error', __('No students were imported. All :count rows were skipped.', ['count' => count($skipped)]))
                ->with('skipped_students', $skipped);
        }

        if (count($skipped) > 0) {
            // Some imported, some skipped
            return redirect()->route('admin.students.index')
                ->with('success', __(':imported students imported successfully. :skipped skipped.', [
                    'imported' => $imported,
                    'skipped' => count($skipped),
                ]))
                ->with('skipped_students', $skipped);
        }

        return redirect()->route('admin.students.index')
            ->with('success', __(':count students imported successfully.', ['count' => $imported]));
    }

    /**
     * Download a CSV template.
     */
    public function template(): StreamedResponse
    {
        $headers = ['name', 'username', 'gender', 'class', 'admission_number', 'date_of_birth', 'address', 'blood_group'];

        return response()->streamDownload(function () use ($headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            // Example row
            fputcsv($handle, ['John Doe', 'john.doe', 'male', 'Primary 1', 'ADM001', '2015-03-15', '123 Main Street', 'O+']);
            fclose($handle);
        }, 'student_import_template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
