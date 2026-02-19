<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Etudiant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'numero_etudiant',
        'universite',
        'faculte',
        'niveau',
        'date_naissance',
        'genre',
        'ville',
        'total_consultations',
    ];

    protected $casts = [
        'date_naissance' => 'date',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function consultations()
    {
        return $this->hasMany(Consultation::class, 'etudiant_id', 'user_id');
    }
}
