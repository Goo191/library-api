<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookResource\Pages;
use App\Models\books;
use App\Models\authors;
use App\Models\categories;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;

class BookResource extends Resource
{
    protected static ?string $model = books::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Books';

    protected static ?string $modelLabel = 'Book';

    protected static ?string $pluralModelLabel = 'Books';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Name')
                    ->required()
                    ->maxLength(255),
               // Forms\Components\Textarea::make('description')
                 //   ->label('Description')
                  //  ->maxLength(1000)
                   // ->columnSpanFull(),
                Forms\Components\Select::make('authors')
                    ->label('Author')
                    ->relationship('authors', 'name')
                    ->required()
                    ->multiple(false)
                    ->preload()
                    ->searchable(),
                Forms\Components\Select::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->required()
                    ->preload(),
                Forms\Components\TextInput::make('quantity')
                    ->label('Copies')
                    ->required()
                    ->numeric()
                    ->minValue(1),
                Forms\Components\TextInput::make('publish_year')
                    ->label('Publication Year')
                    ->numeric()
                    ->minValue(1000)
                    ->maxValue(date('Y')),
                Forms\Components\FileUpload::make('file')
                    ->label('PDF File')
                    ->directory('books')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(10240),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
            //    Tables\Columns\TextColumn::make('description')
              //      ->label('Description')
                //    ->limit(50)
                  //  ->searchable(),
                Tables\Columns\TextColumn::make('authors.name')
                    ->label('Author')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Copies')
                    ->sortable(),
                Tables\Columns\TextColumn::make('publish_year')
                    ->label('Publication Year')
                    ->sortable(),
                Tables\Columns\IconColumn::make('file')
                    ->label('PDF')
                    ->boolean()
                    ->trueIcon('heroicon-o-document')
                    ->falseIcon('heroicon-o-x-mark')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->label('Category'),
            ])
            ->actions([
                Tables\Actions\Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (books $record): string => $record->file ? Storage::url($record->file) : '#')
                    ->openUrlInNewTab()
                    ->visible(fn (books $record): bool => $record->file !== null),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListBooks::route('/'),
            'create' => Pages\CreateBook::route('/create'),
            'edit' => Pages\EditBook::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['authors', 'category']);
    }
} 