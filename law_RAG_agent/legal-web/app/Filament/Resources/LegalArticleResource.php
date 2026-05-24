<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LegalArticleResource\Pages;
use App\Models\LegalArticle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class LegalArticleResource extends Resource
{
    protected static ?string $model = LegalArticle::class;

    // ✨ 需求 7：自定义左侧菜单栏名称与中文标签
    protected static ?string $navigationLabel = '法律条文管理';
    protected static ?string $modelLabel = '法律条文';
    protected static ?string $pluralModelLabel = '法律条文数据';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ✨ 需求 5：支持单个增加数据，按字段填写
                Forms\Components\TextInput::make('doc_uuid')
                    ->label('统一RAG UUID')
                    ->default(fn() => str_replace('-', '', Str::uuid())) // ✨ 修复点
                    ->disabled()
                    ->dehydrated()
                    ->required(),
                Forms\Components\TextInput::make('title')
                    ->label('法律法规名称')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('publish_date')
                    ->label('发布日期')
                    ->maxLength(50),
                Forms\Components\TextInput::make('url')
                    ->label('原文链接')
                    ->maxLength(500),
                Forms\Components\TextInput::make('data_type')
                    ->label('数据类型')
                    ->maxLength(100),
                Forms\Components\TextInput::make('source_module')
                    ->label('来源模块')
                    ->maxLength(255),
                Forms\Components\TextInput::make('crawled_at')
                    ->label('爬取时间')
                    ->maxLength(100),
                // ✨ 需求 6：专属的完整大编辑界面
                Forms\Components\Textarea::make('content')
                    ->label('法律全文正文')
                    ->required()
                    ->rows(20)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('法律名称')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('publish_date')
                    ->label('发布日期')
                    ->sortable(),
                Tables\Columns\TextColumn::make('source_module')
                    ->label('来源模块')
                    ->searchable(),
                // ✨ 需求 6：字段对应内容太长，以省略号表示
                Tables\Columns\TextColumn::make('content')
                    ->label('正文摘要')
                    ->limit(50)
                    ->tooltip(fn($record) => $record->content),
            ])
            // ✨ 需求 2：每页展示30条数据，自动切页
            ->defaultPaginationPageOption(30)
            ->filters([])
            ->actions([
                // 别担心，这里点击后会自动切到专属的全新编辑大界面
                Tables\Actions\EditAction::make(),
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
                                    'url' => $record->url,
                                    'title' => $record->title,
                                    'publish_date' => $record->publish_date,
                                    'content' => $record->content,
                                    'attachments' => $record->attachments,
                                    'crawled_at' => $record->crawled_at,
                                    'data_type' => $record->data_type,
                                    'source_module' => $record->source_module,
                                ];
                            })->toArray();

                            $fileName = 'export_legal_articles_' . time() . '.json';
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
            'index' => Pages\ListLegalArticles::route('/'),
            'create' => Pages\CreateLegalArticle::route('/create'),
            'edit' => Pages\EditLegalArticle::route('/{record}/edit'),
        ];
    }
}
