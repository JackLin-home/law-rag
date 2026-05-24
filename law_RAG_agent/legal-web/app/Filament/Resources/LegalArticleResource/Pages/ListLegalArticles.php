<?php

namespace App\Filament\Resources\LegalArticleResource\Pages;

use App\Filament\Resources\LegalArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms;
use Illuminate\Support\Str;

class ListLegalArticles extends ListRecords
{
    protected static string $resource = LegalArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('单条新增法律条文'),

            // ✨ 需求 4：支持批量上传数据（JSONL 文件）
            Actions\Action::make('importJsonl')
                ->label('批量导入JSONL')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('请选择法律条文的 .jsonl 文件')
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
                            // 完美的字段对齐写入
                            \App\Models\LegalArticle::create([
                                'doc_uuid' => str_replace('-', '', Str::uuid()), // ✨ 修复点
                                'url' => $row['url'] ?? null,
                                'title' => $row['title'] ?? '',
                                'publish_date' => $row['publish_date'] ?? null,
                                'content' => $row['content'] ?? '',
                                'attachments' => $row['attachments'] ?? [],
                                'crawled_at' => $row['crawled_at'] ?? null,
                                'data_type' => $row['data_type'] ?? null,
                                'source_module' => $row['source_module'] ?? null,
                            ]);
                            $insertedCount++;
                        }
                    }
                    fclose($file);
                    unlink($filePath);

                    \Filament\Notifications\Notification::make()
                        ->title('法律条文批量导入成功')
                        ->body("已成功洗入 {$insertedCount} 条法律条文数据！")
                        ->success()
                        ->send();
                }),
        ];
    }
}
