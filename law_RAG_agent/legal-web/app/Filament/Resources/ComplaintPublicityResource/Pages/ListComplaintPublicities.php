<?php

namespace App\Filament\Resources\ComplaintPublicityResource\Pages;

use App\Filament\Resources\ComplaintPublicityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms;
use Illuminate\Support\Str;

class ListComplaintPublicities extends ListRecords
{
    protected static string $resource = ComplaintPublicityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('单条新增公示'),

            // ✨ 需求 4：支持批量上传数据（JSONL 文件）
            Actions\Action::make('importJsonl')
                ->label('批量导入JSONL')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('请选择投诉举报公示的 .jsonl 文件')
                        ->required()
                        ->acceptedFileTypes(['application/octet-stream', 'text/plain', 'application/json'])
                        ->disk('local')
                        ->directory('temp-imports'),
                ])
                ->action(function (array $data) {
                    $filePath = storage_path('app/' . $data['file']);

                    if (!file_exists($filePath)) {
                        return;
                    }

                    $file = fopen($filePath, 'r');
                    $insertedCount = 0;

                    while (($line = fgets($file)) !== false) {
                        $line = trim($line);
                        if (empty($line)) continue;

                        $row = json_decode($line, true);
                        if ($row) {
                            // 完美契合 12315 数据集的爬虫字段对齐写入
                            \App\Models\ComplaintPublicity::create([
                                'doc_uuid' => str_replace('-', '', Str::uuid()), // ✨ 完美修复点
                                'enterprise_name' => $row['enterprise_name'] ?? null,
                                'city_code' => $row['city_code'] ?? null,
                                'issue_type' => $row['issue_type'] ?? null,
                                'case_type' => $row['case_type'] ?? null,
                                'accept_dept' => $row['accept_dept'] ?? null,
                                'reg_time' => $row['reg_time'] ?? null,
                                'end_time' => $row['end_time'] ?? null,
                                'public_time' => $row['public_time'] ?? null,
                                'process_result' => $row['process_result'] ?? '',
                            ]);
                            $insertedCount++;
                        }
                    }
                    fclose($file);
                    unlink($filePath);

                    \Filament\Notifications\Notification::make()
                        ->title('投诉公示批量导入成功')
                        ->body("已成功洗入 {$insertedCount} 条投诉举报公示数据！")
                        ->success()
                        ->send();
                }),
        ];
    }
}
