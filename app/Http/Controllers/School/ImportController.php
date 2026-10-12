<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Jobs\SendParentInvitations;
use App\Models\Import;
use App\Models\User;
use App\Support\ImportException;
use App\Support\ImportReader;
use App\Support\LearnerImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    private function schoolId(): int
    {
        $id = auth()->user()->school_id;
        abort_if(is_null($id), 403, 'Your account is not linked to a school.');
        return $id;
    }

    private function own(Import $import): Import
    {
        abort_unless((int) $import->school_id === $this->schoolId(), 404);
        return $import;
    }

    private function fullPath(Import $import): string
    {
        return Storage::disk('local')->path((string) $import->path);
    }

    public function index()
    {
        return view('school.import.index', [
            'imports' => Import::where('school_id', $this->schoolId())->latest()->take(10)->get(),
        ]);
    }

    public function template()
    {
        $rows = [
            ImportReader::templateColumns(),
            ['1001', 'Sipho', 'Mogale', 'Male', '4', '2016-03-12', 'Mary', 'Mogale', 'Mother', '0821234567', 'mary@example.com', 'Joseph', 'Mogale', 'Father', '0831112222', ''],
            ['1002', 'Lebo', 'Mogale', 'Female', '2', '2018-07-01', 'Mary', 'Mogale', 'Mother', '0821234567', '', '', '', '', '', ''],
            ['1003', 'Thando', 'Nkosi', 'Male', '4', '2016-05-12', 'Thabo', 'Nkosi', 'Father', '0835551111', 'thabo@example.com', '', '', '', '', ''],
        ];

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // so Excel reads the file as UTF-8

            foreach ($rows as $row) {
                fputcsv($out, $row);
            }

            fclose($out);
        }, 'learner-parent-import-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function store(Request $request)
    {
        $schoolId = $this->schoolId();

        $request->validate([
            'file' => 'required|file|max:5120|mimes:xlsx,xls,csv,txt',
        ], [
            'file.required' => 'Choose a file to upload.',
            'file.max'      => 'The file must be 5 MB or smaller.',
            'file.mimes'    => 'Upload an Excel (.xlsx, .xls) or CSV file.',
            'file.uploaded' => 'The upload failed. The file may be larger than the server allows.',
        ]);

        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs('imports/' . $schoolId, Str::uuid() . '.' . $ext, 'local');

        // Catch bad headings straight away
        try {
            ImportReader::read(Storage::disk('local')->path($path), $ext);
        } catch (ImportException $e) {
            Storage::disk('local')->delete($path);

            return back()->withErrors(['file' => $e->getMessage()]);
        }

        $import = Import::create([
            'school_id'     => $schoolId,
            'user_id'       => auth()->id(),
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 240),
            'path'          => $path,
            'status'        => 'previewed',
        ]);

        return redirect()->route('school.import.show', $import);
    }

    public function show(Request $request, Import $import)
    {
        $this->own($import);
                if ($import->status === 'running' && $import->updated_at->lt(now()->subMinutes(15))) {
            $import->update([
                'status' => 'failed',
                'error'  => 'The import stopped unexpectedly. Nothing was saved. Please upload the file again.',
            ]);
        }

        $data = ['import' => $import, 'mode' => $import->status];

        if ($import->status === 'previewed') {
            if (! $import->path || ! Storage::disk('local')->exists($import->path)) {
                $import->update(['status' => 'failed', 'error' => 'The uploaded file is no longer available. Please upload it again.']);

                return redirect()->route('school.import.show', $import);
            }

            try {
                $read   = ImportReader::read($this->fullPath($import), pathinfo($import->path, PATHINFO_EXTENSION));
                $result = (new LearnerImporter($this->schoolId()))->process($read['rows'], false);
            } catch (ImportException $e) {
                return redirect()->route('school.import.index')->withErrors(['file' => $e->getMessage()]);
            }

            $onlyIssues = $request->boolean('issues');
            $all        = collect($result['rows']);
            $filtered   = $onlyIssues ? $all->filter(fn ($r) => $r['errors'] || $r['warnings']) : $all;

            $data += [
                'counts'     => $result['counts'],
                'rows'       => $filtered->take(300),
                'shown'      => min(300, $filtered->count()),
                'totalShown' => $filtered->count(),
                'ignored'    => $read['ignored'],
                'onlyIssues' => $onlyIssues,
            ];
        }

        if ($import->status === 'completed') {
            $parents = User::whereIn('id', $import->new_parent_ids ?? [])->get(['id', 'email', 'invited_at']);

            $data += [
                'counts'     => $import->summary ?? [],
                'issues'     => $import->issues ?? [],
                'inviteable' => $parents->filter(fn ($u) => $u->realEmail() && ! $u->invited_at)->count(),
                'invited'    => $parents->filter(fn ($u) => $u->invited_at)->count(),
                'noEmail'    => $parents->filter(fn ($u) => ! $u->realEmail())->count(),
            ];
        }

        return view('school.import.show', $data);
    }

    public function confirm(Import $import)
    {
        $this->own($import);

        // Only one request may start the import
        $claimed = Import::whereKey($import->id)->where('status', 'previewed')->update(['status' => 'running']);

        if (! $claimed) {
            return redirect()->route('school.import.show', $import);
        }

        @set_time_limit(0);

        try {
            $read     = ImportReader::read($this->fullPath($import), pathinfo($import->path, PATHINFO_EXTENSION));
            $importer = new LearnerImporter($this->schoolId());

            // All or nothing: if anything goes wrong, nothing is saved
            $result = DB::transaction(fn () => $importer->process($read['rows'], true));
        } catch (\Throwable $e) {
            report($e);

            $import->update([
                'status' => 'failed',
                'error'  => $e instanceof ImportException ? $e->getMessage() : 'An unexpected error stopped the import. Nothing was saved. Please try again.',
            ]);

            return redirect()->route('school.import.show', $import);
        }

        // Personal data should not stay on the server longer than needed
        Storage::disk('local')->delete($import->path);

        $import->update([
            'status'         => 'completed',
            'path'           => null,
            'summary'        => $result['counts'],
            'issues'         => LearnerImporter::issuesFrom($result['rows']),
            'new_parent_ids' => $result['new_parent_ids'],
            'completed_at'   => now(),
        ]);

        return redirect()->route('school.import.show', $import)
            ->with('success', 'Import finished.');
    }

    public function issues(Import $import)
    {
        $this->own($import);

        if ($import->status === 'completed') {
            $issues = $import->issues ?? [];
        } elseif ($import->status === 'previewed' && $import->path && Storage::disk('local')->exists($import->path)) {
            try {
                $read   = ImportReader::read($this->fullPath($import), pathinfo($import->path, PATHINFO_EXTENSION));
                $result = (new LearnerImporter($this->schoolId()))->process($read['rows'], false);
                $issues = LearnerImporter::issuesFrom($result['rows'], 5000);
            } catch (ImportException $e) {
                abort(404);
            }
        } else {
            abort(404);
        }

        return response()->streamDownload(function () use ($issues) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Row', 'Admission number', 'Learner', 'Type', 'Message']);

            foreach ($issues as $i) {
                fputcsv($out, [$i['line'], $i['admission'], $i['learner'], $i['level'], $i['message']]);
            }

            fclose($out);
        }, 'import-issues.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function invite(Import $import)
    {
        $this->own($import);
        abort_unless($import->status === 'completed', 404);

        @set_time_limit(0);

        $ids = User::role('parent')
            ->where('school_id', $this->schoolId())
            ->whereIn('id', $import->new_parent_ids ?? [])
            ->whereNull('invited_at')
            ->where('email', 'not like', '%@noemail.invalid')
            ->pluck('id')
            ->all();

        if (! $ids) {
            return back()->with('success', 'Everyone with an email address has already been invited.');
        }

        SendParentInvitations::dispatch($ids);

        return back()->with('success', count($ids) . ' invitation ' . Str::plural('email', count($ids)) . ' are being sent.');
    }

    // Cancel an import that was never confirmed
    public function destroy(Import $import)
    {
        $this->own($import);
        abort_unless($import->status === 'previewed', 404);

        if ($import->path) {
            Storage::disk('local')->delete($import->path);
        }

        $import->delete();

        return redirect()->route('school.import.index')->with('success', 'Import cancelled and the file deleted.');
    }
}