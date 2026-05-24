<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConsultFaqResource\Pages;
use App\Models\ConsultFaq;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ConsultFaqResource extends Resource
{
    protected static ?string $model = ConsultFaq::class;

    // ✨ 需求 7：自定义左侧菜单栏名称与中文标签
    protected static ?string $navigationLabel = '咨询问答管理';
    protected static ?string $modelLabel = '咨询问答';
    protected static ?string $pluralModelLabel = '咨询问答数据';
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ✨ 需求 5：支持单个增加数据，按字段填写
                Forms\Components\TextInput::make('doc_uuid')
                    ->label('统一RAG UUID')
                    ->default(fn() => str_replace('-', '', Str::uuid()))
                    ->disabled()
                    ->dehydrated()
                    ->required(),
                Forms\Components\TextInput::make('title')
                    ->label('咨询标题')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('consult_category')
                    ->label('咨询分类')
                    ->maxLength(255),
                // ✨ 需求 6：专属的完整大编辑界面
                Forms\Components\Textarea::make('content')
                    ->label('咨询与答复整合正文')
                    ->required()
                    ->rows(15)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('doc_uuid')
                    ->label('RAG UUID')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('consult_category')
                    ->label('分类')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label('标题')
                    ->searchable()
                    ->wrap(),
                // ✨ 需求 6：字段太长时，在管理界面以省略号表示 (limit 50个字)
                Tables\Columns\TextColumn::make('content')
                    ->label('正文摘要')
                    ->limit(50)
                    ->tooltip(fn($record) => $record->content),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            // ✨ 需求 2：每页展示30条数据，自动切页
            ->defaultPaginationPageOption(30)
            ->filters([
                // 这里可以加分类筛选
            ])
            ->actions([
                // 编辑时进入专属界面进行修改，而不是弹窗
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
                                    'title' => $record->title,
                                    'content' => $record->content,
                                    'consult_category' => $record->consult_category,
                                ];
                            })->toArray();

                            $fileName = 'export_faqs_' . time() . '.json';
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
        // ✨ 确保编辑和创建都有独立的专属路由页面
        return [
            'index' => Pages\ListConsultFaqs::route('/'),
            'create' => Pages\CreateConsultFaq::route('/create'),
            'edit' => Pages\EditConsultFaq::route('/{record}/edit'),
        ];
    }
}
