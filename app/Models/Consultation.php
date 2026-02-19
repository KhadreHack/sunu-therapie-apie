<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    use HasFactory;

    protected $fillable = [
        'etudiant_id',
        'psychologue_id',
        'type',
        'mode',
        'video_active',
        'statut',
        'date_consultation',
        'date_debut',
        'date_fin',
        'duree_minutes',
        'motif_consultation',
        'motif_refus',
        'anonyme_pour_psy',
        'agora_channel_name',
        'note_etudiant',
        'commentaire_etudiant',
    ];

    protected $casts = [
        'date_consultation' => 'datetime',
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
        'anonyme_pour_psy' => 'boolean',
        'video_active' => 'boolean',
        'note_etudiant' => 'decimal:2',
    ];

    // Relations
    public function etudiant()
    {
        return $this->belongsTo(User::class, 'etudiant_id');
    }

    public function psychologue()
    {
        return $this->belongsTo(User::class, 'psychologue_id');
    }

    public function noteConsultation()
    {
        return $this->hasOne(NoteConsultation::class);
    }

    public function demandeRdv()
    {
        return $this->hasOne(DemandeRdv::class);
    }

    // Scopes
    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    public function scopeAcceptee($query)
    {
        return $query->where('statut', 'acceptee');
    }

    public function scopeEnCours($query)
    {
        return $query->where('statut', 'en_cours');
    }

    public function scopeTerminee($query)
    {
        return $query->where('statut', 'terminee');
    }

    public function scopeUrgence($query)
    {
        return $query->where('type', 'urgence');
    }
}
