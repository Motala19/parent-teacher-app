<?php

namespace App\Support;

use Illuminate\Support\Str;

class ImportReader
{
    public const MAX_ROWS = 3000;

    public const REQUIRED = ['admission_number', 'learner_first_name', 'learner_last_name'];

    public static function spreadsheetsSupported(): bool
    {
        return class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class);
    }

    public static function templateColumns(): array
    {
        $columns = ['admission_number', 'learner_first_name', 'learner_last_name', 'gender', 'grade', 'date_of_birth'];

        foreach ([1, 2] as $n) {
            foreach (['first_name', 'last_name', 'relationship', 'phone', 'email'] as $field) {
                $columns[] = "parent{$n}_{$field}";
            }
        }

        return $columns;
    }

    // Returns ['rows' => [...], 'ignored' => ['Columns we did not use']]
    public static function read(string $path, string $extension): array
    {
        $matrix = in_array(strtolower($extension), ['xlsx', 'xls'], true)
            ? self::fromSpreadsheet($path)
            : self::fromCsv($path);

        $headerIndex = null;
        foreach ($matrix as $i => $cells) {
            if (self::filled($cells)) {
                $headerIndex = $i;
                break;
            }
        }

        if ($headerIndex === null) {
            throw new ImportException('The file is empty.');
        }

        $aliases = self::aliases();
        $columns = [];
        $ignored = [];

        foreach ($matrix[$headerIndex] as $col => $cell) {
            $raw = self::text($cell);

            if ($raw === '') {
                continue;
            }

            $canonical = $aliases[self::normaliseHeader($raw)] ?? null;

            if ($canonical && ! in_array($canonical, $columns, true)) {
                $columns[$col] = $canonical;
            } else {
                $ignored[] = $raw;
            }
        }

        $missing = array_diff(self::REQUIRED, array_values($columns));
        if ($missing) {
            throw new ImportException(
                'These required columns were not found: ' . implode(', ', $missing)
                . '. Download the template to see the expected headings.'
            );
        }

        $rows = [];

        for ($i = $headerIndex + 1; $i < count($matrix); $i++) {
            $cells = $matrix[$i];

            if (! self::filled($cells)) {
                continue;
            }

            $row = array_fill_keys(self::templateColumns(), '');

            foreach ($columns as $col => $canonical) {
                $row[$canonical] = self::text($cells[$col] ?? null);
            }

            $row['_line'] = $i + 1;
            $rows[]       = $row;

            if (count($rows) > self::MAX_ROWS) {
                throw new ImportException('The file has more than ' . number_format(self::MAX_ROWS, 0, '', ' ') . ' learners. Please split it into smaller files.');
            }
        }

        if (! $rows) {
            throw new ImportException('No learner rows were found under the headings.');
        }

        return ['rows' => $rows, 'ignored' => $ignored];
    }

    public static function text($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_float($value)) {
            return floor($value) == $value
                ? sprintf('%.0f', $value)
                : rtrim(rtrim(sprintf('%.6F', $value), '0'), '.');
        }

        return trim((string) $value);
    }

    private static function filled(array $cells): bool
    {
        foreach ($cells as $cell) {
            if (self::text($cell) !== '') {
                return true;
            }
        }

        return false;
    }

    private static function normaliseHeader(string $header): string
    {
        return Str::of($header)->lower()->replaceMatches('/[^a-z0-9]+/', '_')->trim('_')->toString();
    }

    private static function fromSpreadsheet(string $path): array
    {
        if (! self::spreadsheetsSupported()) {
            throw new ImportException('Excel files need one extra package. Run "composer require phpoffice/phpspreadsheet:^1.29", or save the file as CSV and upload that instead.');
        }

        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);

            return $reader->load($path)->getSheet(0)->toArray(null, true, false, false);
        } catch (\Throwable $e) {
            throw new ImportException('That Excel file could not be read. Make sure it is a normal .xlsx or .xls file and is not password protected.');
        }
    }

    private static function fromCsv(string $path): array
    {
        $content = @file_get_contents($path);

        if ($content === false) {
            throw new ImportException('The uploaded file could not be read.');
        }

        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        // Excel in some regions saves CSV with semicolons
        $firstLine = strtok($content, "\n") ?: '';
        $counts    = [',' => substr_count($firstLine, ','), ';' => substr_count($firstLine, ';'), "\t" => substr_count($firstLine, "\t")];
        arsort($counts);
        $delimiter = array_key_first($counts);
        if ($counts[$delimiter] === 0) {
            $delimiter = ',';
        }

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        $data = [];
        while (($row = fgetcsv($stream, 0, $delimiter, '"', '\\')) !== false) {
            $data[] = $row;
        }
        fclose($stream);

        return $data;
    }

    // Accepted headings (case and spacing do not matter) => our column names
    private static function aliases(): array
    {
        $map = [];

        $add = function (string $canonical, array $names) use (&$map) {
            foreach ($names as $name) {
                $map[$name] = $canonical;
            }
        };

        $add('admission_number', ['admission_number', 'admission_no', 'admission', 'admission_nr', 'learner_number', 'learner_no', 'student_number', 'student_no', 'learner_admission_number']);
        $add('learner_first_name', ['learner_first_name', 'first_name', 'firstname', 'learner_name', 'learner_firstname', 'given_name']);
        $add('learner_last_name', ['learner_last_name', 'last_name', 'lastname', 'surname', 'learner_surname', 'learner_lastname', 'family_name']);
        $add('gender', ['gender', 'sex']);
        $add('grade', ['grade', 'gr', 'learner_grade']);
        $add('date_of_birth', ['date_of_birth', 'dob', 'birth_date', 'birthdate', 'birthday']);

        $fields = [
            'first_name'   => ['first_name', 'firstname', 'name_first', 'given_name'],
            'last_name'    => ['last_name', 'lastname', 'surname', 'family_name'],
            'name'         => ['name', 'full_name', 'fullname'],
            'relationship' => ['relationship', 'relation', 'relationship_to_learner'],
            'phone'        => ['phone', 'cell', 'cellphone', 'cell_phone', 'cell_number', 'mobile', 'mobile_number', 'phone_number', 'contact_number', 'tel', 'telephone'],
            'email'        => ['email', 'email_address', 'e_mail'],
        ];

        $prefixes = [
            1 => ['parent1_', 'parent_1_', 'guardian1_', 'guardian_1_', 'p1_', 'parent_', 'guardian_'],
            2 => ['parent2_', 'parent_2_', 'guardian2_', 'guardian_2_', 'p2_'],
        ];

        foreach ($prefixes as $n => $list) {
            foreach ($list as $prefix) {
                foreach ($fields as $field => $names) {
                    foreach ($names as $name) {
                        $map[$prefix . $name] = "parent{$n}_{$field}";
                    }
                }
            }
        }

        return $map;
    }
}