<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceGuideResource\Pages;
use App\Models\ServiceGuide;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ServiceGuideResource extends Resource
{
    protected static ?string $model = ServiceGuide::class;

    // ✨ 需求 7：自定义左侧菜单栏名称与中文标签
    protected static ?string $navigationLabel = '办事指南管理';
    protected static ?string $modelLabel = '办事指南';
    protected static ?string $pluralModelLabel = '办事指南数据';
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ✨ 需求 5：支持单个增加数据，按字段填写
                Forms\Components\Section::make('基本信息')
                    ->schema([
                        Forms\Components\TextInput::make('doc_uuid')
                            ->label('统一RAG UUID')
                            ->default(fn() => str_replace('-', '', Str::uuid())) // ✨ 完美修复点
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        Forms\Components\TextInput::make('title')
                            ->label('指南标题')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('item_name')
                            ->label('主事项名称')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('subitem_name')
                            ->label('二级事项名称')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('st_id')
                            ->label('事项ID')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('guide_id')
                            ->label('指南ID')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('quantitative_restriction')
                            ->label('数量限制说明')
                            ->maxLength(255),
                    ])->columns(2),

                // ✨ 需求 6：长文本专属大编辑界面，独立成块
                Forms\Components\Section::make('核心章节与流程')
                    ->schema([
                        Forms\Components\Textarea::make('application_materials')
                            ->label('申请材料及形式标准')
                            ->rows(8)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('handling_procedures')
                            ->label('办理流程说明')
                            ->rows(8)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('rights_obligations')
                            ->label('权利义务')
                            ->rows(5)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('establishment_basis')
                            ->label('设立依据')
                            ->rows(5)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('faq')
                            ->label('常见问题/错误示例')
                            ->rows(5)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('approved_documents')
                            ->label('审批结果/证件样本')
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
                    ->label('指南标题')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('item_name')
                    ->label('主事项')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('subitem_name')
                    ->label('二级事项')
                    ->searchable()
                    ->toggleable(),
                // ✨ 需求 6：字段太长时，在管理主界面截断并以省略号表示
                Tables\Columns\TextColumn::make('handling_procedures')
                    ->label('办理流程摘要')
                    ->limit(40)
                    ->tooltip(fn($record) => $record->handling_procedures),
                Tables\Columns\TextColumn::make('application_materials')
                    ->label('申请材料摘要')
                    ->limit(40)
                    ->tooltip(fn($record) => $record->application_materials)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            // ✨ 需求 2：每页展示30条数据，自动切页
            ->defaultPaginationPageOption(30)
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(), // 点击后会跳转到上方配置的独立大表单页
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
                                    'item_name' => $record->item_name,
                                    'subitem_name' => $record->subitem_name,
                                    'st_id' => $record->st_id,
                                    'guide_id' => $record->guide_id,
                                    'application_materials' => $record->application_materials,
                                    'rights_obligations' => $record->rights_obligations,
                                    'handling_procedures' => $record->handling_procedures,
                                    'establishment_basis' => $record->establishment_basis,
                                    'faq' => $record->faq,
                                    'approved_documents' => $record->approved_documents,
                                    'quantitative_restriction' => $record->quantitative_restriction,
                                ];
                            })->toArray();

                            $fileName = 'export_service_guides_' . time() . '.json';
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
            'index' => Pages\ListServiceGuides::route('/'),
            'create' => Pages\CreateServiceGuide::route('/create'),
            'edit' => Pages\EditServiceGuide::route('/{record}/edit'),
        ];
    }
}
