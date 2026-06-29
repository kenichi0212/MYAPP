<?php

namespace App\Http\Controllers;

use App\Models\CsvImportBatch;
use App\Models\CsvImportError;
use App\Models\StoreGroup;
use App\Services\Csv\CsvEncodingDetector;
use App\Services\Csv\CsvHeaderMapper;
use App\Services\Csv\CsvMasterDataUpserter;
use App\Services\Csv\CsvRowValidator;
use App\Services\Csv\ProductMatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CsvImportController extends Controller
{
    private const SESSION_KEY = 'csv_import.preview';

    public function create(): View
    {
        return view('csv_imports.create', ['storeGroups' => $this->companyStoreGroups()]);
    }

    public function preview(Request $request): View
    {
        $validated = $request->validate([
            'file' => ['required', 'file'],
            'scope' => ['required', Rule::in(['all_stores', 'store_group'])],
            'store_group_id' => [
                Rule::requiredIf($request->input('scope') === 'store_group'),
                'nullable',
                Rule::exists('store_groups', 'id')->where('company_id', auth()->user()->company_id),
            ],
        ]);

        $detector = new CsvEncodingDetector();
        $contents = $detector->toUtf8(file_get_contents($validated['file']->getRealPath()));

        $lines = array_values(array_filter(
            preg_split('/\r\n|\r|\n/', $contents),
            fn (string $line) => $line !== ''
        ));
        $rows = array_map(str_getcsv(...), $lines);
        $header = array_shift($rows) ?? [];

        $mapper = new CsvHeaderMapper();

        if (! $mapper->hasRequiredHeaders($header)) {
            return back()->withErrors([
                'file' => '必須列がCSVのヘッダーに見つかりません: '.implode('、', $mapper->missingRequiredHeaders($header)),
            ]);
        }

        $rowValidator = new CsvRowValidator();
        $validRows = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // 1行目はヘッダー
            $mapped = $mapper->mapRow($header, $row);
            $rowErrors = $rowValidator->validate($mapped);

            if ($rowErrors === []) {
                $validRows[] = $mapped;
            } else {
                $errors[] = ['row_number' => $rowNumber, 'reason' => implode('、', $rowErrors)];
            }
        }

        session([self::SESSION_KEY => [
            'file_name' => $validated['file']->getClientOriginalName(),
            'scope' => $validated['scope'],
            'store_group_id' => $validated['store_group_id'] ?? null,
            'detected_encoding' => $detector->detect(file_get_contents($validated['file']->getRealPath())),
            'valid_rows' => $validRows,
            'errors' => $errors,
            'total_rows' => count($rows),
        ]]);

        return view('csv_imports.preview', [
            'fileName' => $validated['file']->getClientOriginalName(),
            'totalRows' => count($rows),
            'successCount' => count($validRows),
            'errorCount' => count($errors),
            'errors' => $errors,
        ]);
    }

    public function confirm(): RedirectResponse
    {
        $data = session(self::SESSION_KEY);
        abort_if(! $data, 419, 'プレビュー結果の有効期限が切れました。再度CSVを選択してください。');

        $companyId = auth()->user()->company_id;
        $masterUpserter = new CsvMasterDataUpserter();
        $productMatcher = new ProductMatcher();

        DB::transaction(function () use ($data, $companyId, $masterUpserter, $productMatcher): void {
            $batch = CsvImportBatch::create([
                'company_id' => $companyId,
                'file_name' => $data['file_name'],
                'scope' => $data['scope'],
                'store_group_id' => $data['store_group_id'],
                'imported_by' => auth()->id(),
                'imported_at' => now(),
                'detected_encoding' => $data['detected_encoding'],
                'total_rows' => $data['total_rows'],
                'success_count' => count($data['valid_rows']),
                'error_count' => count($data['errors']),
            ]);

            foreach ($data['valid_rows'] as $row) {
                $storeGroup = $masterUpserter->upsertStoreGroup($companyId, $row['store_group_code'] ?? null, null);
                $store = $masterUpserter->upsertStore(
                    $companyId,
                    $row['store_code'],
                    $row['store_name'] ?? null,
                    $row['office_name'] ?? null,
                    $storeGroup?->id,
                );
                $staff = $masterUpserter->upsertStaffMember($companyId, $row['staff_name'] ?? null);
                $product = $productMatcher->match(
                    $companyId,
                    $row['internal_product_code'] ?? null,
                    $row['jan_code'] ?? null,
                    $row['product_name'],
                    $row['maker_name'] ?? null,
                );

                $masterUpserter->upsertProductStoreAssignment($companyId, $product->id, $store->id, $staff?->id, $batch->id);
            }

            foreach ($data['errors'] as $error) {
                CsvImportError::create([
                    'import_batch_id' => $batch->id,
                    'row_number' => $error['row_number'],
                    'error_reason' => $error['reason'],
                ]);
            }
        });

        session()->forget(self::SESSION_KEY);

        return redirect()->route('csv-imports.create')->with('status', 'CSVの取込が完了しました。');
    }

    private function companyStoreGroups()
    {
        return StoreGroup::where('company_id', auth()->user()->company_id)
            ->orderBy('group_name')
            ->get();
    }
}
