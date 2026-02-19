<?php

namespace App\Filament\Resources\PsychologueResource\Pages;

use App\Filament\Resources\PsychologueResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;

class CreatePsychologue extends CreateRecord
{
    protected static string $resource = PsychologueResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Créer le User d'abord
        $user = User::create([
            'name' => $data['nom'],
            'email' => $data['email'],
            'password' => $data['password'], // Déjà hashé dans le formulaire
            'role' => 'psychologue',
        ]);

        // Créer le Psychologue lié
        $psychologue = $user->psychologue()->create([
            'numero_ordre' => $data['numero_ordre'],
            'specialites' => $data['specialites'] ?? [],
            'bio' => $data['bio'] ?? null,
            'annees_experience' => $data['annees_experience'],
            'diplome' => $data['diplome'],
            'etablissement' => $data['etablissement'] ?? null,
            'disponible' => $data['disponible'] ?? true,
            'urgence' => $data['urgence'] ?? false,
        ]);

        // TODO: Envoyer email avec identifiants au psychologue
        // Mail::to($user->email)->send(new PsychologueCreated($user, $password));

        Notification::make()
            ->title('Psychologue créé avec succès')
            ->body("Email: {$user->email}")
            ->success()
            ->send();

        return $psychologue;
    }
}
