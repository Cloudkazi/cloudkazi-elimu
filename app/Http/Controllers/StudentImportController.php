<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\Student;
use App\Services\PopulationAllocationService;
use App\Services\StudentEnrollmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentImportController extends Controller
{
    /**
     * Authorize viewing the import page.
     * Demo accounts may view the form (read-only).
     * Returns school_id, or null for demo users (no real school).
     */
    protected function authorizeView(): ?int
    {
        $user = Auth::user();

        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        // Demo users: allowed to view (read-only), no school_id available
        if ($user->isDemoAccount()) {
            return null;
        }

        // Must be School Admin or Super Admin
        if (!$user->isSchoolAdmin() && !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized. Only school administrators can access student import.');
        }

        $schoolId = $user->isSuperAdmin()
            ? session('active_school_id')
            : $user->school_id;

        return $schoolId ? (int) $schoolId : null;
    }

    /**
     * Verify that the authenticated user is authorized to perform student management imports.
     * Demo accounts are blocked here.
     */
    protected function authorizeImport(): ?int
    {
        $user = Auth::user();

        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        // Demo accounts are strictly read-only
        if ($user->isDemoAccount()) {
            abort(403, 'Demo accounts are read-only. Student import is disabled.');
        }

        // Must be School Admin, Principal, Deputy Principal, or Super Admin
        if (!$user->isSchoolAdmin() && !$user->isSuperAdmin()) {
            abort(403, 'Unauthorized. Only school administrators can import students.');
        }

        // Determine destination school strictly from user / active session (never from CSV)
        $schoolId = $user->isSuperAdmin()
            ? session('active_school_id')
            : $user->school_id;

        if (!$schoolId) {
            abort(403, 'No destination school context found for student import.');
        }

        return (int) $schoolId;
    }

    /**
     * Display the bulk student CSV import form.
     */
    public function showImportForm()
    {
        $schoolId = $this->authorizeView();

        // Demo users have no school context; show empty classes list
        $classes = $schoolId
            ? SchoolClass::where('school_id', $schoolId)->with('streams')->get()
            : collect();

        return view('students.import', compact('classes', 'schoolId'));
    }


    /**
     * Download the standard CSV template with sample data.
     */
    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="students_import_template.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');

            // Add UTF-8 BOM for Microsoft Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header row
            fputcsv($handle, [
                'admission_number',
                'first_name',
                'last_name',
                'other_names',
                'gender',
                'dob',
                'admission_date',
                'class',
                'stream',
                'curriculum',
                'fee_profile',
                'guardian_name',
                'guardian_phone',
                'guardian_email',
                'guardian_relationship',
            ]);

            // Sample row 1
            fputcsv($handle, [
                'ADM-2026-001',
                'John',
                'Kamau',
                'Mwangi',
                'Male',
                '2015-05-12',
                '2026-01-10',
                'Grade 1',
                'East',
                'CBC',
                'regular',
                'Grace Kamau',
                '0712345678',
                'grace.kamau@example.com',
                'Mother',
            ]);

            // Sample row 2
            fputcsv($handle, [
                'ADM-2026-002',
                'Faith',
                'Wanjiku',
                '',
                'Female',
                '2015-08-22',
                '2026-01-10',
                'Grade 1',
                'West',
                'CBC',
                'regular',
                'Peter Wanjiku',
                '0723456789',
                'peter.wanjiku@example.com',
                'Father',
            ]);

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Process the uploaded CSV file and import new students into the authenticated school.
     */
    public function import(Request $request)
    {
        $schoolId = $this->authorizeImport();

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120', // max 5MB
        ], [
            'csv_file.required' => 'Please select a CSV file to upload.',
            'csv_file.mimes'    => 'The uploaded file must be a valid CSV file (.csv).',
            'csv_file.max'      => 'The CSV file may not be larger than 5MB.',
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return back()->withErrors(['csv_file' => 'Unable to read the uploaded CSV file.']);
        }

        // Read header row (stripping BOM if present)
        $rawHeader = fgetcsv($handle);
        if (!$rawHeader || empty(array_filter($rawHeader))) {
            fclose($handle);
            return back()->withErrors(['csv_file' => 'The uploaded CSV file is empty.']);
        }

        // Clean and normalize header columns
        $header = array_map(function ($col) {
            $clean = preg_replace('/[\x{FEFF}\x{200B}]/u', '', (string) $col);
            return strtolower(trim($clean));
        }, $rawHeader);

        // Required column mapping
        $headerMap = array_flip($header);
        $requiredColumns = ['admission_number', 'first_name', 'last_name', 'gender', 'dob', 'class', 'guardian_name', 'guardian_phone', 'guardian_email'];
        $missingColumns = [];
        foreach ($requiredColumns as $reqCol) {
            if (!isset($headerMap[$reqCol])) {
                $missingColumns[] = $reqCol;
            }
        }

        if (!empty($missingColumns)) {
            fclose($handle);
            return back()->withErrors([
                'csv_file' => 'CSV file is missing required columns: ' . implode(', ', $missingColumns) . '. Please use the downloadable template.'
            ]);
        }

        // Preload school classes and streams for fast, safe tenant lookup
        $schoolClasses = SchoolClass::where('school_id', $schoolId)->with('streams')->get();
        $classLookup = [];
        foreach ($schoolClasses as $c) {
            $classLookup[strtolower(trim($c->name))] = $c;
            $classLookup[(string) $c->id] = $c;
            if (isset($c->class_name)) {
                $classLookup[strtolower(trim($c->class_name))] = $c;
            }
        }

        $streamLookup = [];
        foreach ($schoolClasses as $c) {
            foreach ($c->streams as $s) {
                $streamLookup[$c->id . '_' . strtolower(trim($s->name))] = $s;
                $streamLookup[$c->id . '_' . (string) $s->id] = $s;
            }
        }

        $rowNumber = 1; // Row 1 is header
        $importedCount = 0;
        $failedRows = [];
        $seenAdmissionInFile = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // Skip completely empty lines
            if (empty(array_filter($row, fn($v) => trim((string)$v) !== ''))) {
                continue;
            }

            // Map row data using header positions
            $rowData = [];
            foreach ($headerMap as $colName => $idx) {
                $rowData[$colName] = isset($row[$idx]) ? trim((string)$row[$idx]) : '';
            }

            $admNo = $rowData['admission_number'] ?? '';
            $firstName = $rowData['first_name'] ?? '';
            $lastName = $rowData['last_name'] ?? '';
            $otherNames = $rowData['other_names'] ?? null;
            $genderRaw = $rowData['gender'] ?? '';
            $dobRaw = $rowData['dob'] ?? '';
            $admissionDateRaw = $rowData['admission_date'] ?? '';
            $classRaw = $rowData['class'] ?? '';
            $streamRaw = $rowData['stream'] ?? '';
            $curriculumRaw = strtoupper($rowData['curriculum'] ?? 'CBC');
            $feeProfileRaw = strtolower($rowData['fee_profile'] ?? 'regular');
            $guardianName = $rowData['guardian_name'] ?? '';
            $guardianPhone = $rowData['guardian_phone'] ?? '';
            $guardianEmail = $rowData['guardian_email'] ?? '';
            $guardianRelationship = $rowData['guardian_relationship'] ?? 'Parent';

            // 1. Validate Admission Number
            if ($admNo === '') {
                $failedRows[] = [
                    'row' => $rowNumber,
                    'admission_number' => 'N/A',
                    'reason' => 'Admission number is required.',
                ];
                continue;
            }

            $cleanAdm = strtoupper($admNo);

            // Check duplicate in same CSV file
            if (isset($seenAdmissionInFile[$cleanAdm])) {
                $failedRows[] = [
                    'row' => $rowNumber,
                    'admission_number' => $admNo,
                    'reason' => "Duplicate admission number '{$admNo}' found in uploaded file (previously on row {$seenAdmissionInFile[$cleanAdm]}).",
                ];
                continue;
            }
            $seenAdmissionInFile[$cleanAdm] = $rowNumber;

            // Check duplicate in database for this school
            $existingStudent = Student::where('school_id', $schoolId)
                ->where('admission_number', $cleanAdm)
                ->exists();
            if ($existingStudent) {
                $failedRows[] = [
                    'row' => $rowNumber,
                    'admission_number' => $admNo,
                    'reason' => "Admission number '{$admNo}' already exists in your school records.",
                ];
                continue;
            }

            // 2. Validate Names
            if ($firstName === '' || $lastName === '') {
                $failedRows[] = [
                    'row' => $rowNumber,
                    'admission_number' => $admNo,
                    'reason' => 'Both first name and last name are required.',
                ];
                continue;
            }

            // 3. Validate Gender
            $genderNorm = match (strtolower($genderRaw)) {
                'male', 'm' => 'Male',
                'female', 'f' => 'Female',
                'other', 'o' => 'Other',
                default => null,
            };
            if (!$genderNorm) {
                $failedRows[] = [
                    'row' => $rowNumber,
                    'admission_number' => $admNo,
                    'reason' => "Invalid gender '{$genderRaw}'. Must be Male, Female, or Other.",
                ];
                continue;
            }

            // 4. Validate Date of Birth
            try {
                $dob = Carbon::parse($dobRaw)->format('Y-m-d');
                if (Carbon::parse($dob)->isFuture()) {
                    throw new \Exception('Date of birth cannot be in the future.');
                }
            } catch (\Throwable $e) {
                $failedRows[] = [
                    'row' => $rowNumber,
                    'admission_number' => $admNo,
                    'reason' => "Invalid date of birth '{$dobRaw}'. Expected format YYYY-MM-DD.",
                ];
                continue;
            }

            // 5. Validate Admission Date
            if ($admissionDateRaw !== '') {
                try {
                    $admissionDate = Carbon::parse($admissionDateRaw)->format('Y-m-d');
                } catch (\Throwable $e) {
                    $failedRows[] = [
                        'row' => $rowNumber,
                        'admission_number' => $admNo,
                        'reason' => "Invalid admission date '{$admissionDateRaw}'. Expected format YYYY-MM-DD.",
                    ];
                    continue;
                }
            } else {
                $admissionDate = now()->toDateString();
            }

            // 6. Validate Class (must exist in authenticated school)
            $classKey = strtolower(trim($classRaw));
            if (!isset($classLookup[$classKey])) {
                $failedRows[] = [
                    'row' => $rowNumber,
                    'admission_number' => $admNo,
                    'reason' => "Class '{$classRaw}' does not exist in your school records.",
                ];
                continue;
            }
            $targetClass = $classLookup[$classKey];

            // 7. Validate Stream (optional, but if provided must exist in class)
            $targetStream = null;
            if ($streamRaw !== '') {
                $streamKey = $targetClass->id . '_' . strtolower(trim($streamRaw));
                if (isset($streamLookup[$streamKey])) {
                    $targetStream = $streamLookup[$streamKey];
                } else {
                    $failedRows[] = [
                        'row' => $rowNumber,
                        'admission_number' => $admNo,
                        'reason' => "Stream '{$streamRaw}' was not found for class '{$targetClass->name}'.",
                    ];
                    continue;
                }
            }

            // 8. Validate Curriculum
            $curriculum = in_array($curriculumRaw, ['CBC', '8-4-4'], true)
                ? $curriculumRaw
                : ($targetClass->curriculum ?? 'CBC');

            // 9. Validate Fee Profile
            $validProfiles = ['regular', 'boarder', 'transport', 'boarder_transport'];
            $feeProfile = in_array($feeProfileRaw, $validProfiles, true) ? $feeProfileRaw : 'regular';

            // 10. Validate Guardian details
            if ($guardianName === '' || $guardianPhone === '') {
                $failedRows[] = [
                    'row' => $rowNumber,
                    'admission_number' => $admNo,
                    'reason' => 'Guardian name and guardian phone number are required.',
                ];
                continue;
            }

            // Clean guardian phone
            $cleanPhone = preg_replace('/[^0-9]/', '', $guardianPhone);
            if (strlen($cleanPhone) < 7) {
                $failedRows[] = [
                    'row' => $rowNumber,
                    'admission_number' => $admNo,
                    'reason' => "Invalid guardian phone number '{$guardianPhone}'.",
                ];
                continue;
            }

            if ($guardianEmail === '' || !filter_var($guardianEmail, FILTER_VALIDATE_EMAIL)) {
                $failedRows[] = [
                    'row' => $rowNumber,
                    'admission_number' => $admNo,
                    'reason' => 'A valid guardian email address is required to create the parent login.',
                ];
                continue;
            }

            // 11. Population allocation capacity check
            if (!PopulationAllocationService::canCreateStudent((int) $schoolId)) {
                $limit = PopulationAllocationService::getStudentLimit((int) $schoolId);
                $failedRows[] = [
                    'row' => $rowNumber,
                    'admission_number' => $admNo,
                    'reason' => "Student Limit Reached: Current subscription allows up to {$limit} students.",
                ];
                continue;
            }

            // 12. Atomic Enrollment Transaction per valid row
            try {
                StudentEnrollmentService::enroll([
                    'school_id'            => $schoolId,
                    'admission_number'     => $cleanAdm,
                    'first_name'           => $firstName,
                    'last_name'            => $lastName,
                    'other_names'          => $otherNames ?: null,
                    'gender'               => $genderNorm,
                    'dob'                  => $dob,
                    'admission_date'       => $admissionDate,
                    'school_class_id'      => $targetClass->id,
                    'stream_id'            => $targetStream?->id,
                    'curriculum'           => $curriculum,
                    'fee_profile'          => $feeProfile,
                    'guardian_name'        => $guardianName,
                    'guardian_phone'       => $cleanPhone,
                    'guardian_email'       => $guardianEmail,
                    'guardian_relationship'=> $guardianRelationship ?: 'Parent',
                    'status'               => 'active',
                ]);

                $importedCount++;
            } catch (\Throwable $e) {
                $failedRows[] = [
                    'row' => $rowNumber,
                    'admission_number' => $admNo,
                    'reason' => 'Import error: ' . $e->getMessage(),
                ];
            }
        }

        fclose($handle);

        $totalProcessed = $importedCount + count($failedRows);

        $resultSummary = [
            'total'      => $totalProcessed,
            'imported'   => $importedCount,
            'failed'     => count($failedRows),
            'failed_rows'=> $failedRows,
        ];

        if ($importedCount > 0 && count($failedRows) === 0) {
            $msg = "Student Import Complete: All {$importedCount} student(s) imported successfully.";
            return back()->with('success', $msg)->with('importResult', $resultSummary);
        } elseif ($importedCount > 0 && count($failedRows) > 0) {
            $msg = "Student Import Completed with warnings: {$importedCount} imported, " . count($failedRows) . " failed.";
            return back()->with('warning', $msg)->with('importResult', $resultSummary);
        } else {
            $msg = "Student Import Failed: 0 of {$totalProcessed} rows imported. Review error details below.";
            return back()->withErrors(['csv_file' => $msg])->with('importResult', $resultSummary);
        }
    }
}
