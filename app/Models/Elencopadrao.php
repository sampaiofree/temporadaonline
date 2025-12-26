<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Jogo;

class Elencopadrao extends Model
{
    protected $table = 'elencopadrao';

    protected $fillable = [
        'jogo_id',
        'player_id',
        'player_url',
        'short_name',
        'long_name',
        'player_positions',
        'overall',
        'potential',
        'value_eur',
        'wage_eur',
        'age',
        'dob',
        'height_cm',
        'weight_kg',
        'league_name',
        'league_level',
        'club_name',
        'club_position',
        'club_jersey_number',
        'club_loaned_from',
        'club_joined_date',
        'club_contract_valid_until_year',
        'nationality_id',
        'nationality_name',
        'nation_team_id',
        'nation_position',
        'nation_jersey_number',
        'preferred_foot',
        'weak_foot',
        'skill_moves',
        'international_reputation',
        'work_rate',
        'body_type',
        'real_face',
        'release_clause_eur',
        'player_tags',
        'player_traits',
        'pace',
        'shooting',
        'passing',
        'dribbling',
        'defending',
        'physic',
        'attacking_crossing',
        'attacking_finishing',
        'attacking_heading_accuracy',
        'attacking_short_passing',
        'attacking_volleys',
        'skill_dribbling',
        'skill_curve',
        'skill_fk_accuracy',
        'skill_long_passing',
        'skill_ball_control',
        'movement_acceleration',
        'movement_sprint_speed',
        'movement_agility',
        'movement_reactions',
        'movement_balance',
        'power_shot_power',
        'power_jumping',
        'power_stamina',
        'power_strength',
        'power_long_shots',
        'mentality_aggression',
        'mentality_interceptions',
        'mentality_positioning',
        'mentality_vision',
        'mentality_penalties',
        'mentality_composure',
        'defending_marking_awareness',
        'defending_standing_tackle',
        'defending_sliding_tackle',
        'goalkeeping_diving',
        'goalkeeping_handling',
        'goalkeeping_kicking',
        'goalkeeping_positioning',
        'goalkeeping_reflexes',
        'goalkeeping_speed',
        'ls',
        'st',
        'rs',
        'lw',
        'lf',
        'cf',
        'rf',
        'rw',
        'lam',
        'cam',
        'ram',
        'lm',
        'lcm',
        'cm',
        'rcm',
        'rm',
        'lwb',
        'ldm',
        'cdm',
        'rdm',
        'rwb',
        'lb',
        'lcb',
        'cb',
        'rcb',
        'rb',
        'gk',
        'player_face_url',
    ];

    protected $casts = [
        'overall' => 'integer',
        'potential' => 'integer',
        'value_eur' => 'integer',
        'wage_eur' => 'integer',
        'age' => 'integer',
        'dob' => 'date',
        'height_cm' => 'integer',
        'weight_kg' => 'integer',
        'league_level' => 'integer',
        'club_jersey_number' => 'integer',
        'club_joined_date' => 'date',
        'club_contract_valid_until_year' => 'integer',
        'nationality_id' => 'integer',
        'nation_team_id' => 'integer',
        'nation_jersey_number' => 'integer',
        'weak_foot' => 'integer',
        'skill_moves' => 'integer',
        'international_reputation' => 'integer',
        'real_face' => 'boolean',
        'release_clause_eur' => 'integer',
    ];

    public function jogo(): BelongsTo
    {
        return $this->belongsTo(Jogo::class);
    }

    public function ligaClubeElencos(): HasMany
    {
        return $this->hasMany(LigaClubeElenco::class, 'elencopadrao_id');
    }

    public function ligaTransferencias(): HasMany
    {
        return $this->hasMany(LigaTransferencia::class, 'elencopadrao_id');
    }
}
