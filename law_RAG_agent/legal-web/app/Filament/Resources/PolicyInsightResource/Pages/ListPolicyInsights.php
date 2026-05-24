<?php

namespace App\Filament\Resources\PolicyInsightResource\Pages;

use App\Filament\Resources\PolicyInsightResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms;
use Illuminate\Support\Str;

class ListPolicyInsights extends ListRecords
{
    protected static string $resource = PolicyInsightResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('单条新增政策资讯'),

            // ✨ 需求 4：支持批量上传数据（JSONL 文件）
            Actions\Action::make('importJsonl')
                ->label('批量导入JSONL')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('请选择政策资讯的 .jsonl 文件')
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
                            // 完美契合政策资讯的真实爬虫字段写入
                            \App\Models\PolicyInsight::create([
                                'doc_uuid' => str_replace('-', '', Str::uuid()), // ✨ 完美修复点
                                'title' => $row['title'] ?? '',
                                'content' => $row['content'] ?? '',
                            ]);
                            $insertedCount++;
                        }
                    }
                    fclose($file);
                    unlink($filePath);

                    \Filament\Notifications\Notification::make()
                        ->title('政策资讯批量导入成功')
                        ->body("已成功洗入 {$insertedCount} 条政策资讯数据！")
                        ->success()
                        ->send();
                }),
        ];
    }
}
