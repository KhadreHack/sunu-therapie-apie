<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Psychologue extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'numero_ordre',
        'specialites',
        'bio',
        'annees_experience',
        'diplome',
        'etablissement',
        'disponible',
        'urgence',
        'note_moyenne',
        'total_consultations',
    ];

    protected $casts = [
        'specialites' => 'array',
        'disponible' => 'boolean',
        'urgence' => 'boolean',
        'note_moyenne' => 'decimal:2',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function consultations()
    {
        return $this->hasMany(Consultation::class, 'psychologue_id', 'user_id');
    }

    public function disponibilites()
    {
        return $this->hasMany(Disponibilite::class, 'psychologue_id', 'user_id');
    }

    public function notesConsultations()
    {
        return $this->hasMany(NoteConsultation::class, 'psychologue_id', 'user_id');
    }
}
