<?php

namespace App\Filament\Resources\ConsultFaqResource\Pages;

use App\Filament\Resources\ConsultFaqResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms;
use Illuminate\Support\Str;

class ListConsultFaqs extends ListRecords
{
    protected static string $resource = ConsultFaqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('单条新增问答'),

            // ✨ 需求 4：支持批量上传数据（JSONL 文件）
            Actions\Action::make('importJsonl')
                ->label('批量导入JSONL')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('请选择你的 .jsonl 文件')
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

                    // 逐行读取 JSONL 格式
                    while (($line = fgets($file)) !== false) {
                        $line = trim($line);
                        if (empty($line)) continue;

                        $row = json_decode($line, true);
                        if ($row) {
                            // 自动在后台对齐数据表字段并补全 RAG UUID
                            \App\Models\ConsultFaq::create([
                                'doc_uuid' => (string) Str::uuid()->replace('-', ''),
                                'title' => $row['title'] ?? '',
                                'content' => $row['content'] ?? '',
                                'consult_category' => $row['consult_category'] ?? null,
                            ]);
                            $insertedCount++;
                        }
                    }
                    fclose($file);
                    unlink($filePath); // 导入后自动销毁临时上传文件

                    // 右上角弹出优雅的大厂质感通知
                    \Filament\Notifications\Notification::make()
                        ->title('批量导入成功')
                        ->body("成功从 JSONL 文件中洗入 {$insertedCount} 条新数据！")
                        ->success()
                        ->send();
                }),
        ];
    }
}
