<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PenaltyDecisionResource\Pages;
use App\Models\PenaltyDecision;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PenaltyDecisionResource extends Resource
{
    protected static ?string $model = PenaltyDecision::class;

    // ✨ 需求 7：自定义左侧菜单栏名称与中文标签
    protected static ?string $navigationLabel = '行政处罚决定书管理';
    protected static ?string $modelLabel = '行政处罚决定书';
    protected static ?string $pluralModelLabel = '行政处罚决定书数据';
    protected static ?string $navigationIcon = 'heroicon-o-scale'; // ✅ 换成正义天平

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ✨ 需求 5：支持单个增加数据，按字段填写
                Forms\Components\Section::make('处罚决定基础信息')
                    ->schema([
                        Forms\Components\TextInput::make('doc_uuid')
                            ->label('统一RAG UUID')
                            ->default(fn() => str_replace('-', '', Str::uuid())) // ✨ 完美修复点
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        Forms\Components\TextInput::make('docid')
                            ->label('决定书文号ID')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('party_name')
                            ->label('当事人/企业名称')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('penalty_authority')
                            ->label('处罚机关')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('penalty_type')
                            ->label('处罚类型')
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('法律依据')
                    ->schema([
                        // ✨ 需求 6：专属的完整大编辑界面
                        Forms\Components\Textarea::make('penalty_basis')
                            ->label('处罚依据 / 法定条款原文')
                            ->required()
                            ->rows(10)
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('docid')
                    ->label('文号ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('party_name')
                    ->label('当事人/企业')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('penalty_authority')
                    ->label('处罚机关')
                    ->searchable(),
                Tables\Columns\TextColumn::make('penalty_type')
                    ->label('处罚类型')
                    ->badge()
                    ->color('danger'),
                // ✨ 需求 6：字段太长时，在管理主界面截断并以省略号表示
                Tables\Columns\TextColumn::make('penalty_basis')
                    ->label('处罚依据摘要')
                    ->limit(40)
                    ->tooltip(fn($record) => $record->penalty_basis),
            ])
            // ✨ 需求 2：每页展示30条数据，自动切页
            ->defaultPaginationPageOption(30)
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(), // 点击后切到独立专属全屏修改页
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    // ✨ 需求 3：支持批量导出选中的数据为 JSON 文件
                    Tables\Actions\BulkAction::make('exportJson')
                        ->label('批量导出JSON')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $data = $records->map(function ($record) {
                                return [
                                    'docid' => $record->docid,
                                    'party_name' => $record->party_name,
                                    'penalty_authority' => $record->penalty_authority,
                                    'penalty_type' => $record->penalty_type,
                                    'penalty_basis' => $record->penalty_basis,
                                ];
                            })->toArray();

                            $fileName = 'export_penalties_' . time() . '.json';
                            Storage::disk('public')->put($fileName, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

                            return response()->download(storage_path('app/public/' . $fileName))->deleteFileAfterSend(true);
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPenaltyDecisions::route('/'),
            'create' => Pages\CreatePenaltyDecision::route('/create'),
            'edit' => Pages\EditPenaltyDecision::route('/{record}/edit'),
        ];
    }
}
