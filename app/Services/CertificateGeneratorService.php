<?php

namespace App\Services;

use App\Imports\GenericArrayImport;
use App\Models\CertificateGenerate;
use App\Models\CertificateItem;
use App\Models\CertificateTemplate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use setasign\Fpdi\Fpdi;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

class CertificateGeneratorService
{
    public const MAX_ROWS = 5;

    /**
     * @return array{generate: CertificateGenerate, url: string|null, failedRows: array}
     */
    public function generate(CertificateTemplate $template, string $mode, array $manualRow = [], ?string $csvPath = null): array
    {
        $rows = $mode === 'manual'
            ? [$this->normalizeRow($manualRow)]
            : $this->buildCsvRows($template, $manualRow, $csvPath);

        $folder = 'certificate/generates/generate_' . now()->format('Ymd_His');
        $generate = CertificateGenerate::create([
            'certificate_template_id' => $template->uuid,
            'mode' => $mode,
            'total_requested' => count($rows),
            'total_success' => 0,
            'total_failed' => 0,
            'failed_detail' => null,
            'file_path' => null,
        ]);

        $files = [];
        $failedRows = [];

        foreach ($rows as $index => $row) {
            try {
                $files[] = $this->generateOnePdf($template, $generate, $row, $folder, $index);
            } catch (\Throwable $e) {
                $failedRows[] = $this->rowLabel($row, $index) . ': ' . $e->getMessage();
            }
        }

        $filePath = count($files) === 1 ? $files[0] : null;

        $generate->update([
            'total_success' => count($files),
            'total_failed' => count($failedRows),
            'failed_detail' => $failedRows ? implode('; ', $failedRows) : null,
            'file_path' => $filePath,
        ]);

        return [
            'generate' => $generate,
            'url' => $filePath ? Storage::disk('public')->url($filePath) : null,
            'failedRows' => $failedRows,
        ];
    }

    protected function normalizeRow(array $row): array
    {
        return [
            'data' => $row['data'] ?? [],
            'valid_from' => $row['valid_from'] ?? null,
            'valid_until' => $row['valid_until'] ?? null,
            'deskripsi' => $row['deskripsi'] ?? null,
            'catatan' => $row['catatan'] ?? null,
        ];
    }

    protected function rowLabel(array $row, int $index): string
    {
        foreach (($row['data'] ?? []) as $value) {
            if (filled($value)) {
                return (string) $value;
            }
        }

        return 'Baris ' . ($index + 1);
    }

    public function readHeaders(string $path): array
    {
        $rows = $this->readRawRows($path);

        if (empty($rows)) {
            return [];
        }

        return array_map(fn($v) => trim((string) $v), $rows[0]);
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    protected function readRawRows(string $path): array
    {
        $import = new GenericArrayImport();
        Excel::import($import, $path);

        // Maatwebsite\Excel selalu bungkus hasil per-sheet; ambil sheet pertama.
        $rows = $import->rows;
        if (isset($rows[0]) && is_array($rows[0]) && isset($rows[0][0]) && is_array($rows[0][0])) {
            $rows = $rows[0];
        }

        return $rows;
    }

    protected function buildCsvRows(CertificateTemplate $template, array $formData, string $path): array
    {
        $rawRows = $this->readRawRows($path);

        if (empty($rawRows)) {
            throw new \RuntimeException('File kosong atau format tidak dikenali.');
        }

        $headers = array_map(fn($v) => trim((string) $v), $rawRows[0]);
        $dataRows = array_slice($rawRows, 1, self::MAX_ROWS);

        $mapping = $formData['mapping'] ?? [];
        $mappingMode = $formData['mapping_mode'] ?? [];
        $fixedValues = $formData['fixed_values'] ?? [];

        $printedFields = $template->fields
            ->filter(fn($f) => ! $f->is_archived && $f->field_key !== CertificateTemplate::RESERVED_QRCODE_KEY)
            ->sortBy('created_at')
            ->values();

        $resolveDateField = function (string $key, array $assocRow) use ($mappingMode, $mapping, $fixedValues) {
            $useColumn = ($mappingMode[$key] ?? 'fixed') === 'column';

            if ($useColumn) {
                $col = $mapping[$key] ?? null;
                return $col !== null ? ($assocRow[$col] ?? null) : null;
            }

            return $fixedValues[$key] ?? null;
        };

        $deskripsiTemplate = (string) ($fixedValues['deskripsi'] ?? '');
        $catatanTemplate = (string) ($fixedValues['catatan'] ?? '');

        $rows = [];
        foreach ($dataRows as $rawRow) {
            // baris kosong (biasanya sisa baris kosong di Excel) — skip
            if (collect($rawRow)->every(fn($v) => trim((string) $v) === '')) {
                continue;
            }

            $assocRow = [];
            foreach ($headers as $i => $h) {
                $assocRow[$h] = $rawRow[$i] ?? null;
            }

            $data = [];
            foreach ($printedFields as $i => $field) {
                $col = $mapping[$i] ?? null;
                $data[$field->field_key] = $col !== null ? (string) ($assocRow[$col] ?? '') : '';
            }

            $replacements = [];
            foreach ($data as $key => $value) {
                $replacements['{' . $key . '}'] = $value;
            }

            $rows[] = [
                'data' => $data,
                'deskripsi' => strtr($deskripsiTemplate, $replacements),
                'catatan' => strtr($catatanTemplate, $replacements),
                'valid_from' => $resolveDateField('valid_from', $assocRow),
                'valid_until' => $resolveDateField('valid_until', $assocRow),
            ];
        }

        if (empty($rows)) {
            throw new \RuntimeException('Tidak ada baris data yang bisa diproses (file kosong setelah baris header).');
        }

        return $rows;
    }

    protected function generateOnePdf(CertificateTemplate $template, CertificateGenerate $generate, array $row, string $folder, int $index): string
    {
        $templatePath = Storage::disk('public')->path($template->background_image);

        $slug = (string) Str::ulid();

        $pdf = new Fpdi('P', 'pt');
        $pdf->setSourceFile($templatePath);
        $templateId = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($templateId);

        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($templateId);

        $qrRelativePath = null;
        $fieldsSnapshot = [];

        foreach ($template->fields as $field) {
            if ($field->is_archived) {
                continue;
            }

            $x = ($field->x / 100) * $size['width'];
            $y = ($field->y / 100) * $size['height'];

            if ($field->field_key === CertificateTemplate::RESERVED_QRCODE_KEY) {
                $verifyUrl = route('certificate', $slug);

                $qrSize = (int) $field->font_size;

                $qrCode = Builder::create()
                    ->writer(new PngWriter())
                    ->writerOptions([])
                    ->data($verifyUrl)
                    ->encoding(new Encoding('UTF-8'))
                    ->errorCorrectionLevel(ErrorCorrectionLevel::High)
                    ->size($qrSize * 4)
                    ->margin(10)
                    ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
                    ->build();

                $qrRelativePath = "certificate/qrcodes/{$slug}.png";
                Storage::disk('public')->makeDirectory('certificate/qrcodes');
                Storage::disk('public')->put($qrRelativePath, $qrCode->getString());

                $qrAbsPath = Storage::disk('public')->path($qrRelativePath);
                $pdf->Image($qrAbsPath, $x - ($qrSize / 2), $y - ($qrSize / 2), $qrSize, $qrSize);

                $field->increment('usage_count');
                continue;
            }

            $value = (string) ($row['data'][$field->field_key] ?? '');

            [$r, $g, $b] = $this->hexToRgb($field->font_color);

            $fontFamily = in_array($field->font_family, ['Helvetica', 'Times', 'Courier'], true)
                ? $field->font_family
                : 'Helvetica';

            $fontStyle = ($field->font_bold ? 'B' : '') . ($field->font_underline ? 'U' : '');

            $pdf->SetTextColor($r, $g, $b);
            $pdf->SetFont($fontFamily, $fontStyle, (int) $field->font_size);

            $textWidth = $pdf->GetStringWidth($value);

            $textAlign = in_array($field->text_align, ['left', 'center', 'right'], true)
                ? $field->text_align
                : 'center';

            $textX = match ($textAlign) {
                'left' => $x,
                'right' => $x - $textWidth,
                default => $x - ($textWidth / 2),
            };

            $baselineY = $y + ($field->font_size * 0.35);
            $pdf->Text($textX, $baselineY, $value);

            $field->increment('usage_count');

            $fieldsSnapshot[] = [
                'key' => $field->field_key,
                'label' => $field->label,
                'value' => $value,
            ];
        }

        $identifier = $this->rowLabel($row, $index);
        $filename = $folder . '/' . Str::slug($identifier) . '-' . $slug . '.pdf';

        Storage::disk('public')->makeDirectory($folder);
        $pdf->Output(Storage::disk('public')->path($filename), 'F');

        CertificateItem::create([
            'certificate_generate_id' => $generate->uuid,
            'certificate_template_id' => $template->uuid,
            'slug' => $slug,
            'fields' => $fieldsSnapshot,
            'deskripsi' => $row['deskripsi'] ?? null,
            'catatan' => $row['catatan'] ?? null,
            'valid_from' => $row['valid_from'] ?? null,
            'valid_until' => $row['valid_until'] ?? null,
            'file_path' => $filename,
            'qr_path' => $qrRelativePath,
        ]);

        return $filename;
    }

    protected function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
