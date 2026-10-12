<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Review;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
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
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Resources';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([

                // ===== BARIS ATAS: info review (kiri) + avatar (kanan) =====
                Section::make('Informasi Review')
                    ->description('Nama pemberi review, rating, dan tautan sumber review.')
                    ->icon('heroicon-o-star')
                    ->columnSpan(['lg' => 2])
                    ->columns(2)
                    ->schema([
                        TextInput::make('review_fullname')
                            ->label('Nama Lengkap')
                            ->placeholder('Contoh: Budi Santoso')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('review_rating')
                            ->label('Rating')
                            ->placeholder('5')
                            ->suffix('/ 5')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(5)
                            ->required(),

                        TextInput::make('review_link')
                            ->label('Link Review')
                            ->placeholder('https://maps.app.goo.gl/...')
                            ->prefixIcon('heroicon-o-link')
                            ->helperText('Tulis lengkap dengan https://')
                            ->live(onBlur: true)
                            ->url()
                            ->afterStateUpdated(fn(Set $set, ?string $state) => $set('review_slug', Str::slug($state)))
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('review_slug')
                            ->label('Slug')
                            ->placeholder('Terisi otomatis dari link')
                            ->prefixIcon('heroicon-o-hashtag')
                            ->helperText('Terisi otomatis sesuai link review.')
                            ->readOnly()
                            ->dehydrated()
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('Foto Profil')
                    ->icon('heroicon-o-user-circle')
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        FileUpload::make('review_avatar')
                            ->hiddenLabel()
                            ->image()
                            ->disk('public')
                            ->directory('img_review')
                            ->optimize('webp')
                            ->maxSize(2048)
                            ->downloadable()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '1:1',
                                '4:3',
                                '16:9',
                            ])
                            ->imagePreviewHeight('160')
                            ->helperText('Rasio 1:1 disarankan, maks. 2 MB.')
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, $get): string {
                                $slug = Str::of('review_' . ($get('review_fullname') ?: 'review'))
                                    ->lower()
                                    ->slug('_')
                                    ->limit(50);

                                return $slug . '_' . time() . '.' . $file->getClientOriginalExtension();
                            })
                            ->required(),
                    ]),

                // ===== BARIS BAWAH: isi review selebar penuh =====
                Section::make('Isi Review')
                    ->description('Tulis testimoni dari pemberi review.')
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->columnSpanFull()
                    ->schema([
                        RichEditor::make('review_content')
                            ->hiddenLabel()
                            ->toolbarButtons([
                                'bold',
                                'bulletList',
                                'link',
                                'orderedList',
                                'underline',
                                'undo',
                            ])
                            ->extraInputAttributes(['style' => 'min-height: 14rem;'])
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('review_avatar')
                    ->label('AVATAR')
                    ->circular()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('review_fullname')
                    ->label('FULLNAME')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('review_rating')
                    ->label('RATING')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('review_link')
                    ->label('LINK')
                    ->url(fn($record) => $record->review_link)
                    ->openUrlInNewTab()
                    ->limit('30')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('created_at')
                    ->label('LOG')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])->emptyStateActions([
                Action::make('create')
                    ->label('Create Review')
                    ->url(route('filament.management.resources.reviews.create'))
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
            'index' => Pages\ListReviews::route('/'),
            'create' => Pages\CreateReview::route('/create'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
        ];
    }
}
