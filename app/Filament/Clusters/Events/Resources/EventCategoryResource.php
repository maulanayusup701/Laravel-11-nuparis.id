<?php

namespace App\Filament\Clusters\Events\Resources;

use App\Filament\Clusters\Events;
use App\Filament\Clusters\Events\Resources\EventCategoryResource\Pages;
use App\Models\EventCategory;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class EventCategoryResource extends Resource
{
    protected static ?string $model = EventCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $cluster = Events::class;
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Event Category';
    protected static ?string $pluralModelLabel = 'Event Category';

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Cluster hilang dari sidebar
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Section::make('Informasi Kategori')
                    ->description('Nama kategori dan slug untuk URL.')
                    ->icon('heroicon-o-tag')
                    ->columns(2)
                    ->schema([
                        TextInput::make('event_category_name')
                            ->label('Nama Kategori')
                            ->placeholder('Contoh: Workshop')
                            ->prefixIcon('heroicon-m-pencil')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(Set $set, ?string $state) => $set('event_category_slug', Str::slug($state)))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('event_category_slug')
                            ->label('Slug (URL)')
                            ->placeholder('Terisi otomatis dari nama')
                            ->prefixIcon('heroicon-o-link')
                            ->helperText('Terisi otomatis sesuai nama kategori.')
                            ->readOnly()
                            ->dehydrated()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('event_category_name')
                    ->label('Nama Kategori')
                    ->weight('medium')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('event_category_slug')
                    ->label('Slug')
                    ->color('gray')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada kategori')
            ->emptyStateIcon('heroicon-o-tag')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Tambah Kategori')
                    ->url(route('filament.management.events.resources.event-categories.create'))
                    ->icon('heroicon-m-plus')
                    ->button(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEventCategories::route('/'),
            'create' => Pages\CreateEventCategory::route('/create'),
            'edit' => Pages\EditEventCategory::route('/{record}/edit'),
        ];
    }
}
