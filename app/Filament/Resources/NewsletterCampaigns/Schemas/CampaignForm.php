<?php

namespace App\Filament\Resources\NewsletterCampaigns\Schemas;

use App\Models\NewsletterSubscriber;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Message')
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('subject')
                            ->label('Objet')
                            ->required()
                            ->maxLength(150)
                            ->placeholder('Ex. : -20 % sur l’électroménager ce week-end')
                            ->helperText('Court et concret : c’est lui qui donne envie d’ouvrir.'),
                        TextInput::make('preheader')
                            ->label('Ligne d’aperçu')
                            ->maxLength(150)
                            ->placeholder('Ex. : jusqu’à dimanche minuit, livraison offerte dès 50 000 FCFA')
                            ->helperText('Affichée à côté de l’objet dans la boîte de réception, pas dans le message.'),
                        // JPEG or PNG on purpose: Outlook does not show WebP images.
                        FileUpload::make('image')
                            ->label('Image de couverture')
                            ->disk('storefront')
                            ->directory('uploads/newsletter')
                            ->visibility('public')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png'])
                            ->maxSize(2048)
                            ->helperText('Facultative. JPEG ou PNG, 1200 × 600 px conseillé, 2 Mo maximum.'),
                        RichEditor::make('content')
                            ->label('Contenu')
                            ->required()
                            ->toolbarButtons([
                                ['bold', 'italic', 'underline', 'link'],
                                ['h2', 'h3'],
                                ['bulletList', 'orderedList', 'blockquote'],
                                ['undo', 'redo'],
                            ]),
                    ]),
                Section::make('Bouton')
                    ->description('Facultatif : un bouton orange sous le message.')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('button_label')->label('Texte du bouton')->maxLength(60)->placeholder('Voir les offres')->requiredWith('button_url'),
                        TextInput::make('button_url')->label('Lien')->url()->maxLength(500)->placeholder(url('/boutique'))->requiredWith('button_label'),
                    ]),
                Section::make('Destinataires')
                    ->columnStart(3)
                    ->columnSpan(1)
                    ->description(fn () => NewsletterSubscriber::active()->count().' abonné(s) actif(s) aujourd’hui. La liste est figée au moment de l’envoi ; les désinscrits ne reçoivent rien.'),
            ]);
    }
}
