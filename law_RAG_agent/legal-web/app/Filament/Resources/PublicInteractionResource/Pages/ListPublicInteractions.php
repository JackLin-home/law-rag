<?php

namespace App\Filament\Resources\PublicInteractionResource\Pages;

use App\Filament\Resources\PublicInteractionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms;
use Illuminate\Support\Str;

class ListPublicInteractions extends ListRecords
{
    protected static string $resource = PublicInteractionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('单条新增政民互动'),

            // ✨ 需求 4：支持批量上传数据（JSONL 文件）
            Actions\Action::make('importJsonl')
                ->label('批量导入JSONL')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('请选择政民互动的 .jsonl 文件')
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
                            // 完美契合政民互动数据集的爬虫字段对齐写入
                            \App\Models\PublicInteraction::create([
                                'doc_uuid' => str_replace('-', '', Str::uuid()), // ✨ 完美修复点
                                'title' => $row['title'] ?? '',
                                'consult_id' => $row['consult_id'] ?? null,
                                'consult_category' => $row['consult_category'] ?? null,
                                'consult_time' => $row['consult_time'] ?? null,
                                'reply_unit' => $row['reply_unit'] ?? null,
                                'reply_time' => $row['reply_time'] ?? null,
                                'question' => $row['question'] ?? '',
                                'answer' => $row['answer'] ?? '',
                            ]);
                            $insertedCount++;
                        }
                    }
                    fclose($file);
                    unlink($filePath);

                    \Filament\Notifications\Notification::make()
                        ->title('政民互动批量导入成功')
                        ->body("已成功洗入 {$insertedCount} 条政民互动数据！")
                        ->success()
                        ->send();
                }),
        ];
    }
}
