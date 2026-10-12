<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Info;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;

class Settings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog';
    protected static ?string $navigationLabel = 'Pengaturan Website';
    protected static ?int $navigationSort = 60;
    protected static string $view = 'filament.pages.settings';
    protected static ?string $navigationGroup = 'Settings';

    public Info $info;

    public array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('page_Settings');
    }

    public function mount(): void
    {
        $this->info = Info::firstOrFail();
        $this->form->fill($this->info->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Meta Settings')
                    ->description('section untuk mengatur kebutuhan meta data')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('meta_domain')
                                ->label('Domain')
                                ->prefix('https://')
                                ->url()
                                ->required(),

                            TextInput::make('meta_title')
                                ->label('Title')
                                ->required(),

                            TextArea::make('meta_desc')
                                ->label('Description')
                                ->required()
                                ->autosize(),

                            FileUpload::make('meta_image')
                                ->label('Image')
                                ->image()
                                ->disk('public')->directory('meta')
                                ->optimize('webp')
                                ->required()
                                ->downloadable()
                                ->imageEditor()
                                ->imageEditorAspectRatios([
                                    '16:9',
                                    '4:3',
                                    '1:1',
                                ]),
                        ]),
                    ]),
                Section::make('Website Settings')
                    ->description('section untuk mengatur informasi website')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('no_whatsapp')
                                ->label('No. Telepon')
                                ->required(),

                            TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->required(),

                            TextInput::make('youtube')
                                ->label('Youtube')
                                ->required(),

                            TextInput::make('instagram')
                                ->label('Instagram')
                                ->required(),

                            Textarea::make('address')
                                ->label('Alamat')
                                ->required()
                                ->columnSpanFull()
                                ->autosize(),
                            RichEditor::make('privacy_policy')
                                ->toolbarButtons([
                                    'bold',
                                    'bulletList',
                                    'link',
                                    'orderedList',
                                    'underline',
                                    'undo',
                                ])->columnSpanFull(),
                            RichEditor::make('terms_conditions')
                                ->toolbarButtons([
                                    'bold',
                                    'bulletList',
                                    'link',
                                    'orderedList',
                                    'underline',
                                    'undo',
                                ])->columnSpanFull(),
                        ]),
                    ])->columns(2),

                Section::make('Halaman Tentang Kami')
                    ->description('Konten halaman Tentang Kami dalam dua bahasa (ID dan EN)')
                    ->collapsible()
                    ->schema([
                        FileUpload::make('about_image')
                            ->label('Foto Tentang Kami')
                            ->image()
                            ->disk('public')->directory('about')
                            ->optimize('webp')
                            ->downloadable()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '1:1',
                            ]),
                        Tabs::make('about_tabs')->tabs([
                            Tabs\Tab::make('Hero & Siapa Kami')->schema([
                                Grid::make(2)->schema([
                                    Textarea::make('about_us.hero.id')
                                        ->label('Hero (ID)')
                                        ->rows(3),
                                    Textarea::make('about_us.hero.en')
                                        ->label('Hero (EN)')
                                        ->rows(3),
                                    Textarea::make('about_us.who.id')
                                        ->label('Siapa Kami (ID)')
                                        ->helperText('Pisahkan paragraf dengan satu baris kosong')
                                        ->rows(10),
                                    Textarea::make('about_us.who.en')
                                        ->label('Who We Are (EN)')
                                        ->helperText('Separate paragraphs with one blank line')
                                        ->rows(10),
                                ]),
                            ]),

                            Tabs\Tab::make('Layanan Utama')->schema([
                                Repeater::make('about_us.services')
                                    ->label('Layanan')
                                    ->columns(2)
                                    ->collapsible()
                                    ->itemLabel(fn(array $state): ?string => $state['title_id'] ?? null)
                                    ->addActionLabel('Tambah layanan')
                                    ->schema([
                                        TextInput::make('icon')
                                            ->label('Ikon (Font Awesome)')
                                            ->placeholder('fa-flask-vial'),
                                        TextInput::make('kbli')
                                            ->label('Kode KBLI')
                                            ->placeholder('KBLI 7120X'),
                                        TextInput::make('title_id')->label('Judul (ID)'),
                                        TextInput::make('title_en')->label('Title (EN)'),
                                        Textarea::make('desc_id')->label('Deskripsi (ID)')->rows(4),
                                        Textarea::make('desc_en')->label('Description (EN)')->rows(4),
                                    ]),
                            ]),

                            Tabs\Tab::make('Mengapa Memilih Kami')->schema([
                                Repeater::make('about_us.values')
                                    ->label('Keunggulan')
                                    ->columns(2)
                                    ->collapsible()
                                    ->itemLabel(fn(array $state): ?string => $state['title_id'] ?? null)
                                    ->addActionLabel('Tambah keunggulan')
                                    ->schema([
                                        TextInput::make('icon')
                                            ->label('Ikon (Font Awesome)')
                                            ->placeholder('fa-shield-halved')
                                            ->columnSpanFull(),
                                        TextInput::make('title_id')->label('Judul (ID)'),
                                        TextInput::make('title_en')->label('Title (EN)'),
                                        Textarea::make('desc_id')->label('Deskripsi (ID)')->rows(3),
                                        Textarea::make('desc_en')->label('Description (EN)')->rows(3),
                                    ]),
                            ]),

                            Tabs\Tab::make('Visi & Misi')->schema([
                                Grid::make(2)->schema([
                                    Textarea::make('vision.id')
                                        ->label('Visi (ID)')
                                        ->rows(4),
                                    Textarea::make('vision.en')
                                        ->label('Vision (EN)')
                                        ->rows(4),
                                ]),
                                Repeater::make('mission')
                                    ->label('Misi')
                                    ->columns(2)
                                    ->addActionLabel('Tambah misi')
                                    ->schema([
                                        Textarea::make('id')->label('Misi (ID)')->rows(3),
                                        Textarea::make('en')->label('Mission (EN)')->rows(3),
                                    ]),
                            ]),
                        ])->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        // dd($data);
        $this->info->update($data);

        Notification::make()
            ->title('Pengaturan Diperbarui')
            ->success()
            ->body('Informasi pengaturan berhasil disimpan.')
            ->send();
    }
}
