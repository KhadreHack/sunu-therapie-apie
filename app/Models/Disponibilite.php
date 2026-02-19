<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Disponibilite extends Model
{
    use HasFactory;

    protected $fillable = [
        'psychologue_id',
        'jour_semaine',
        'heure_debut',
        'heure_fin',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    // Relations
    public function psychologue()
    {
        return $this->belongsTo(User::class, 'psychologue_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }
}
