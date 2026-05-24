<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PolicyInsightResource\Pages;
use App\Models\PolicyInsight;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PolicyInsightResource extends Resource
{
    protected static ?string $model = PolicyInsight::class;

    // ✨ 需求 7：自定义左侧菜单栏名称与中文标签
    protected static ?string $navigationLabel = '政策资讯管理';
    protected static ?string $modelLabel = '政策资讯';
    protected static ?string $pluralModelLabel = '政策资讯数据';
    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ✨ 需求 5：支持单个增加数据，按字段填写
                Forms\Components\Section::make('政策资讯基本信息')
                    ->schema([
                        Forms\Components\TextInput::make('doc_uuid')
                            ->label('统一RAG UUID')
                            ->default(fn() => str_replace('-', '', Str::uuid())) // ✨ 完美修复点
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        Forms\Components\TextInput::make('title')
                            ->label('政策新闻标题')
                            ->required()
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('新闻正文')
                    ->schema([
                        // ✨ 需求 6：内容太长时，提供专属宽敞的完整大编辑界面
                        Forms\Components\Textarea::make('content')
                            ->label('新闻资讯全文内容')
                            ->required()
                            ->rows(15)
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
                    ->label('政策新闻标题')
                    ->searchable()
                    ->wrap(),
                // ✨ 需求 6：字段对应内容太长的话，在管理界面以省略号表示 (截断50字)
                Tables\Columns\TextColumn::make('content')
                    ->label('资讯正文摘要')
                    ->limit(50)
                    ->tooltip(fn($record) => $record->content),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('导入时间')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            // ✨ 需求 2：每页展示30条数据，自动切页
            ->defaultPaginationPageOption(30)
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(), // 点击后流畅切换到专属的大幅修改页面
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
                                    'content' => $record->content,
                                ];
                            })->toArray();

                            $fileName = 'export_policy_insights_' . time() . '.json';
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
            'index' => Pages\ListPolicyInsights::route('/'),
            'create' => Pages\CreatePolicyInsight::route('/create'),
            'edit' => Pages\EditPolicyInsight::route('/{record}/edit'),
        ];
    }
}
