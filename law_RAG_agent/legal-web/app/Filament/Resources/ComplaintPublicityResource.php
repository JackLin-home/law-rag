<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ComplaintPublicityResource\Pages;
use App\Models\ComplaintPublicity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ComplaintPublicityResource extends Resource
{
    protected static ?string $model = ComplaintPublicity::class;

    // ✨ 需求 7：自定义左侧菜单栏名称与中文标签
    protected static ?string $navigationLabel = '投诉举报公示管理';
    protected static ?string $modelLabel = '投诉举报公示';
    protected static ?string $pluralModelLabel = '投诉举报公示数据';
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ✨ 需求 5：支持单个增加数据，按字段填写
                Forms\Components\Section::make('案件基础信息')
                    ->schema([
                        Forms\Components\TextInput::make('doc_uuid')
                            ->label('统一RAG UUID')
                            ->default(fn() => str_replace('-', '', Str::uuid())) // ✨ 完美修复点
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        Forms\Components\TextInput::make('enterprise_name')
                            ->label('涉事企业名称')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('city_code')
                            ->label('行政区划代码')
                            ->maxLength(50),
                        Forms\Components\TextInput::make('issue_type')
                            ->label('问题类型(如:猪油)')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('case_type')
                            ->label('投诉举报类别(如:食品安全)')
                            ->maxLength(100),
                        Forms\Components\TextInput::make('accept_dept')
                            ->label('受理部门')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('时间线与处理结果')
                    ->schema([
                        Forms\Components\TextInput::make('reg_time')
                            ->label('登记时间')
                            ->maxLength(50),
                        Forms\Components\TextInput::make('end_time')
                            ->label('办结时间')
                            ->maxLength(50),
                        Forms\Components\TextInput::make('public_time')
                            ->label('公示时间')
                            ->maxLength(50),
                        // ✨ 需求 6：专属的完整大编辑界面
                        Forms\Components\Textarea::make('process_result')
                            ->label('处理结果说明')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),
                    ])->columns(3)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('enterprise_name')
                    ->label('企业名称')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('case_type')
                    ->label('举报类别')
                    ->searchable(),
                Tables\Columns\TextColumn::make('issue_type')
                    ->label('问题类型')
                    ->searchable(),
                Tables\Columns\TextColumn::make('public_time')
                    ->label('公示时间')
                    ->sortable(),
                // ✨ 需求 6：字段太长时，在管理主界面截断并以省略号表示
                Tables\Columns\TextColumn::make('process_result')
                    ->label('处理结果')
                    ->limit(40)
                    ->tooltip(fn($record) => $record->process_result),
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
                                    'enterprise_name' => $record->enterprise_name,
                                    'city_code' => $record->city_code,
                                    'issue_type' => $record->issue_type,
                                    'case_type' => $record->case_type,
                                    'accept_dept' => $record->accept_dept,
                                    'reg_time' => $record->reg_time,
                                    'end_time' => $record->end_time,
                                    'public_time' => $record->public_time,
                                    'process_result' => $record->process_result,
                                ];
                            })->toArray();

                            $fileName = 'export_complaints_' . time() . '.json';
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
            'index' => Pages\ListComplaintPublicities::route('/'),
            'create' => Pages\CreateComplaintPublicity::route('/create'),
            'edit' => Pages\EditComplaintPublicity::route('/{record}/edit'),
        ];
    }
}
