<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PublicInteractionResource\Pages;
use App\Models\PublicInteraction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PublicInteractionResource extends Resource
{
    protected static ?string $model = PublicInteraction::class;

    // ✨ 需求 7：自定义左侧菜单栏名称与中文标签
    protected static ?string $navigationLabel = '政民互动管理';
    protected static ?string $modelLabel = '政民互动';
    protected static ?string $pluralModelLabel = '政民互动数据';
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ✨ 需求 5：支持单个增加数据，按字段填写
                Forms\Components\Section::make('互动索引与分类')
                    ->schema([
                        Forms\Components\TextInput::make('doc_uuid')
                            ->label('统一RAG UUID')
                            ->default(fn() => str_replace('-', '', Str::uuid())) // ✨ 完美修复点
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        Forms\Components\TextInput::make('title')
                            ->label('咨询标题')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('consult_id')
                            ->label('咨询ID')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('consult_category')
                            ->label('咨询分类(如:消保)')
                            ->maxLength(100),
                    ])->columns(2),

                Forms\Components\Section::make('处理流程与时效')
                    ->schema([
                        Forms\Components\TextInput::make('reply_unit')
                            ->label('答复单位')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('consult_time')
                            ->label('咨询时间')
                            ->maxLength(50),
                        Forms\Components\TextInput::make('reply_time')
                            ->label('回复时间')
                            ->maxLength(50),
                    ])->columns(3),

                Forms\Components\Section::make('互动核心内容')
                    ->schema([
                        // ✨ 需求 6：内容太长时，提供独立、大尺度的专属编辑正文页
                        Forms\Components\Textarea::make('question')
                            ->label('群众提问内容')
                            ->required()
                            ->rows(8)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('answer')
                            ->label('官方答复内容')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('咨询标题')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('consult_category')
                    ->label('分类')
                    ->searchable(),
                Tables\Columns\TextColumn::make('reply_unit')
                    ->label('答复单位')
                    ->searchable(),
                // ✨ 需求 6：字段对应内容太长的话，在管理主界面以省略号表示
                Tables\Columns\TextColumn::make('question')
                    ->label('群众提问摘要')
                    ->limit(35)
                    ->tooltip(fn($record) => $record->question),
                Tables\Columns\TextColumn::make('answer')
                    ->label('官方回复摘要')
                    ->limit(35)
                    ->tooltip(fn($record) => $record->answer),
            ])
            // ✨ 需求 2：每页展示30条数据，自动切页
            ->defaultPaginationPageOption(30)
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(), // 点击后流畅切换到专属的修改页面
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
                                    'title' => $record->title,
                                    'consult_id' => $record->consult_id,
                                    'consult_category' => $record->consult_category,
                                    'consult_time' => $record->consult_time,
                                    'reply_unit' => $record->reply_unit,
                                    'reply_time' => $record->reply_time,
                                    'question' => $record->question,
                                    'answer' => $record->answer,
                                ];
                            })->toArray();

                            $fileName = 'export_interactions_' . time() . '.json';
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
            'index' => Pages\ListPublicInteractions::route('/'),
            'create' => Pages\CreatePublicInteraction::route('/create'),
            'edit' => Pages\EditPublicInteraction::route('/{record}/edit'),
        ];
    }
}
