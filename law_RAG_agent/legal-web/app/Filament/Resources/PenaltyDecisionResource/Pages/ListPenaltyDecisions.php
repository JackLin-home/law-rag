<?php

namespace App\Filament\Resources\PenaltyDecisionResource\Pages;

use App\Filament\Resources\PenaltyDecisionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms;
use Illuminate\Support\Str;

class ListPenaltyDecisions extends ListRecords
{
    protected static string $resource = PenaltyDecisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('单条新增处罚文书'),

            // ✨ 需求 4：支持批量上传数据（JSONL 文件）
            Actions\Action::make('importJsonl')
                ->label('批量导入JSONL')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label('请选择行政处罚的 .jsonl 文件')
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
                            // 完美契合行政处罚数据集的爬虫字段对齐写入
                            \App\Models\PenaltyDecision::create([
                                'doc_uuid' => str_replace('-', '', Str::uuid()), // ✨ 完美修复点
                                'docid' => $row['docid'] ?? null,
                                'party_name' => $row['party_name'] ?? '',
                                'penalty_authority' => $row['penalty_authority'] ?? '',
                                'penalty_type' => $row['penalty_type'] ?? '',
                                'penalty_basis' => $row['penalty_basis'] ?? '',
                            ]);
                            $insertedCount++;
                        }
                    }
                    fclose($file);
                    unlink($filePath);

                    \Filament\Notifications\Notification::make()
                        ->title('处罚文书批量导入成功')
                        ->body("已成功洗入 {$insertedCount} 条行政处罚数据！")
                        ->success()
                        ->send();
                }),
        ];
    }
}
