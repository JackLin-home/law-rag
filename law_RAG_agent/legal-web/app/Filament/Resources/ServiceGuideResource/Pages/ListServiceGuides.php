<?php

namespace App\Filament\Resources\ServiceGuideResource\Pages;

use App\Filament\Resources\ServiceGuideResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms;
use Illuminate\Support\Str;

class ListServiceGuides extends ListRecords
{
    protected static string $resource = ServiceGuideResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('单条新增办事指南'),

            // ✨ 需求 4：支持批量上传数据（JSONL 文件）
            Actions\Action::make('importJsonl')
                ->label('批量导入JSONL')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('请选择办事指南的 .jsonl 文件')
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
                            // 完美的真实数据集爬虫字段对齐写入
                            \App\Models\ServiceGuide::create([
                                'doc_uuid' => str_replace('-', '', Str::uuid()), // ✨ 完美修复点
                                'title' => $row['title'] ?? '',
                                'item_name' => $row['item_name'] ?? null,
                                'subitem_name' => $row['subitem_name'] ?? null,
                                'st_id' => $row['st_id'] ?? null,
                                'guide_id' => $row['guide_id'] ?? null,
                                'application_materials' => $row['application_materials'] ?? null,
                                'rights_obligations' => $row['rights_obligations'] ?? null,
                                'handling_procedures' => $row['handling_procedures'] ?? null,
                                'establishment_basis' => $row['establishment_basis'] ?? null,
                                'faq' => $row['faq'] ?? null,
                                'approved_documents' => $row['approved_documents'] ?? null,
                                'quantitative_restriction' => $row['quantitative_restriction'] ?? null,
                            ]);
                            $insertedCount++;
                        }
                    }
                    fclose($file);
                    unlink($filePath);

                    \Filament\Notifications\Notification::make()
                        ->title('办事指南批量导入成功')
                        ->body("已成功洗入 {$insertedCount} 条办事指南数据！")
                        ->success()
                        ->send();
                }),
        ];
    }
}
