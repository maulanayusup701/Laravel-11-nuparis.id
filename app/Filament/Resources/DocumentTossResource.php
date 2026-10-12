<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentTossResource\Pages;
use App\Models\DocumentToss;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentTossResource extends Resource
{
    protected static ?string $model = DocumentToss::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationGroup = 'TOSS';
    protected static ?string $navigationLabel = 'Document Toss';
    protected static ?string $modelLabel = 'Document Toss';
    protected static ?string $pluralModelLabel = 'Document Toss';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([

                // ================= KOLOM KIRI =================
                Grid::make(1)
                    ->columnSpan(['lg' => 2])
                    ->schema([

                        Section::make('Informasi Dokumen')
                            ->description('Kode tiket, nama, dan pihak yang terlibat.')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Grid::make(2)
                                    ->extraAttributes(['class' => 'gap-y-3'])
                                    ->schema([
                                        TextInput::make('ticket_code')
                                            ->label('Kode Tiket')
                                            ->placeholder('Contoh: TKT-0001')
                                            ->prefixIcon('heroicon-m-ticket')
                                            ->required()
                                            ->maxLength(255),

                                        TextInput::make('document_no')
                                            ->label('Nomor Dokumen')
                                            ->placeholder('Contoh: 001/SK/X/2026')
                                            ->prefixIcon('heroicon-m-hashtag')
                                            ->maxLength(255),

                                        TextInput::make('document_name')
                                            ->label('Nama Dokumen')
                                            ->placeholder('Contoh: Surat Keterangan Kerja')
                                            ->prefixIcon('heroicon-m-document')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        RichEditor::make('document_description')
                                            ->label('Deskripsi Dokumen')
                                            ->placeholder('Ringkasan singkat isi dokumen')
                                            ->toolbarButtons([
                                                'bold',
                                                'bulletList',
                                                'italic',
                                                'link',
                                                'orderedList',
                                                'underline',
                                                'undo',
                                            ])
                                            ->extraInputAttributes(['style' => 'min-height: 10rem;'])
                                            ->columnSpanFull(),

                                        TextInput::make('document_bySign')
                                            ->label('Ditandatangani Oleh')
                                            ->placeholder('Nama penandatangan')
                                            ->prefixIcon('heroicon-m-pencil-square')
                                            ->maxLength(255),

                                        TextInput::make('document_toReceive')
                                            ->label('Diterima Oleh')
                                            ->placeholder('Nama penerima')
                                            ->prefixIcon('heroicon-m-inbox-arrow-down')
                                            ->maxLength(255),

                                        Select::make('document_action')
                                            ->label('Tindakan Dokumen')
                                            ->placeholder('Pilih tindakan dokumen')
                                            ->options([
                                                'Tanda Tangan' => 'Tanda Tangan',
                                            ])
                                            ->required()
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Section::make('Catatan')
                            ->description('Catatan tambahan terkait dokumen.')
                            ->icon('heroicon-o-chat-bubble-bottom-center-text')
                            ->schema([
                                RichEditor::make('document_notes')
                                    ->hiddenLabel()
                                    ->placeholder('Catatan tambahan')
                                    ->toolbarButtons([
                                        'blockquote',
                                        'bold',
                                        'bulletList',
                                        'codeBlock',
                                        'h2',
                                        'h3',
                                        'italic',
                                        'link',
                                        'orderedList',
                                        'strike',
                                        'underline',
                                    ])
                                    ->extraInputAttributes(['style' => 'min-height: 16rem;'])
                                    ->required()
                                    ->columnSpanFull(),
                            ]),
                    ]),

                // ================= KOLOM KANAN (sidebar) =================
                Grid::make(1)
                    ->columnSpan(['lg' => 1])
                    ->schema([

                        Section::make('Berkas')
                            ->description('Lokasi file dan slug halaman publik.')
                            ->icon('heroicon-o-folder-open')
                            ->schema([
                                TextInput::make('document_path')
                                    ->label('Path Dokumen')
                                    ->prefixIcon('heroicon-m-folder')
                                    ->maxLength(255),

                                TextInput::make('document_final_path')
                                    ->label('Path Dokumen Final')
                                    ->prefixIcon('heroicon-m-document-check')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('document_slug')
                                    ->label('Slug (URL)')
                                    ->prefixIcon('heroicon-o-link')
                                    ->helperText('Dipakai untuk link /toss/{slug}.')
                                    ->required()
                                    ->maxLength(255),
                            ]),

                        Section::make('Tanda Tangan QR')
                            ->description('Posisi dan ukuran QR pada dokumen.')
                            ->icon('heroicon-o-qr-code')
                            ->schema([
                                TextInput::make('qr_path')
                                    ->label('Path QR')
                                    ->prefixIcon('heroicon-m-qr-code')
                                    ->maxLength(255),

                                Grid::make(3)
                                    ->extraAttributes(['class' => 'gap-y-3'])
                                    ->schema([
                                        TextInput::make('qr_position_x')
                                            ->label('Posisi X')
                                            ->numeric(),

                                        TextInput::make('qr_position_y')
                                            ->label('Posisi Y')
                                            ->numeric(),

                                        TextInput::make('qr_scale')
                                            ->label('Skala')
                                            ->numeric(),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ticket_code')
                    ->label('Kode Tiket')
                    ->weight('medium')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('document_no')
                    ->label('No. Dokumen')
                    ->searchable(),

                TextColumn::make('document_name')
                    ->label('Nama Dokumen')
                    ->limit(50)
                    ->sortable()
                    ->searchable(),

                TextColumn::make('document_bySign')
                    ->label('Ditandatangani Oleh')
                    ->searchable(),

                TextColumn::make('document_toReceive')
                    ->label('Diterima Oleh')
                    ->searchable(),

                TextColumn::make('document_action')
                    ->label('Tindakan')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Action::make('visit')
                    ->label('Visit')
                    ->icon('heroicon-o-link')
                    ->url(fn($record) => config('app.url') . '/toss/' . $record->document_slug)
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada dokumen')
            ->emptyStateIcon('heroicon-o-document');
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
            'index' => Pages\ListDocumentTosses::route('/'),
            'create' => Pages\CreateDocumentToss::route('/create'),
            'edit' => Pages\EditDocumentToss::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
