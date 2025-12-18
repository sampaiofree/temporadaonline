<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('elencopadrao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jogo_id')->constrained('jogos')->cascadeOnDelete();
            $table->string('player_id')->nullable()->index();
            $table->string('player_url')->nullable();
            $table->string('short_name')->nullable();
            $table->string('long_name')->nullable();
            $table->string('player_positions')->nullable();
            $table->unsignedSmallInteger('overall')->nullable();
            $table->unsignedSmallInteger('potential')->nullable();
            $table->unsignedBigInteger('value_eur')->nullable();
            $table->unsignedBigInteger('wage_eur')->nullable();
            $table->unsignedSmallInteger('age')->nullable();
            $table->date('dob')->nullable();
            $table->unsignedSmallInteger('height_cm')->nullable();
            $table->unsignedSmallInteger('weight_kg')->nullable();
            $table->string('league_name')->nullable();
            $table->unsignedTinyInteger('league_level')->nullable();
            $table->string('club_name')->nullable();
            $table->string('club_position')->nullable();
            $table->unsignedSmallInteger('club_jersey_number')->nullable();
            $table->string('club_loaned_from')->nullable();
            $table->date('club_joined_date')->nullable();
            $table->unsignedSmallInteger('club_contract_valid_until_year')->nullable();
            $table->unsignedInteger('nationality_id')->nullable();
            $table->string('nationality_name')->nullable();
            $table->unsignedInteger('nation_team_id')->nullable();
            $table->string('nation_position')->nullable();
            $table->unsignedSmallInteger('nation_jersey_number')->nullable();
            $table->string('preferred_foot')->nullable();
            $table->unsignedTinyInteger('weak_foot')->nullable();
            $table->unsignedTinyInteger('skill_moves')->nullable();
            $table->unsignedTinyInteger('international_reputation')->nullable();
            $table->string('work_rate')->nullable();
            $table->string('body_type')->nullable();
            $table->boolean('real_face')->nullable();
            $table->unsignedBigInteger('release_clause_eur')->nullable();
            $table->text('player_tags')->nullable();
            $table->text('player_traits')->nullable();
            $table->unsignedTinyInteger('pace')->nullable();
            $table->unsignedTinyInteger('shooting')->nullable();
            $table->unsignedTinyInteger('passing')->nullable();
            $table->unsignedTinyInteger('dribbling')->nullable();
            $table->unsignedTinyInteger('defending')->nullable();
            $table->unsignedTinyInteger('physic')->nullable();
            $table->unsignedTinyInteger('attacking_crossing')->nullable();
            $table->unsignedTinyInteger('attacking_finishing')->nullable();
            $table->unsignedTinyInteger('attacking_heading_accuracy')->nullable();
            $table->unsignedTinyInteger('attacking_short_passing')->nullable();
            $table->unsignedTinyInteger('attacking_volleys')->nullable();
            $table->unsignedTinyInteger('skill_dribbling')->nullable();
            $table->unsignedTinyInteger('skill_curve')->nullable();
            $table->unsignedTinyInteger('skill_fk_accuracy')->nullable();
            $table->unsignedTinyInteger('skill_long_passing')->nullable();
            $table->unsignedTinyInteger('skill_ball_control')->nullable();
            $table->unsignedTinyInteger('movement_acceleration')->nullable();
            $table->unsignedTinyInteger('movement_sprint_speed')->nullable();
            $table->unsignedTinyInteger('movement_agility')->nullable();
            $table->unsignedTinyInteger('movement_reactions')->nullable();
            $table->unsignedTinyInteger('movement_balance')->nullable();
            $table->unsignedTinyInteger('power_shot_power')->nullable();
            $table->unsignedTinyInteger('power_jumping')->nullable();
            $table->unsignedTinyInteger('power_stamina')->nullable();
            $table->unsignedTinyInteger('power_strength')->nullable();
            $table->unsignedTinyInteger('power_long_shots')->nullable();
            $table->unsignedTinyInteger('mentality_aggression')->nullable();
            $table->unsignedTinyInteger('mentality_interceptions')->nullable();
            $table->unsignedTinyInteger('mentality_positioning')->nullable();
            $table->unsignedTinyInteger('mentality_vision')->nullable();
            $table->unsignedTinyInteger('mentality_penalties')->nullable();
            $table->unsignedTinyInteger('mentality_composure')->nullable();
            $table->unsignedTinyInteger('defending_marking_awareness')->nullable();
            $table->unsignedTinyInteger('defending_standing_tackle')->nullable();
            $table->unsignedTinyInteger('defending_sliding_tackle')->nullable();
            $table->unsignedTinyInteger('goalkeeping_diving')->nullable();
            $table->unsignedTinyInteger('goalkeeping_handling')->nullable();
            $table->unsignedTinyInteger('goalkeeping_kicking')->nullable();
            $table->unsignedTinyInteger('goalkeeping_positioning')->nullable();
            $table->unsignedTinyInteger('goalkeeping_reflexes')->nullable();
            $table->unsignedTinyInteger('goalkeeping_speed')->nullable();
            $table->string('ls')->nullable();
            $table->string('st')->nullable();
            $table->string('rs')->nullable();
            $table->string('lw')->nullable();
            $table->string('lf')->nullable();
            $table->string('cf')->nullable();
            $table->string('rf')->nullable();
            $table->string('rw')->nullable();
            $table->string('lam')->nullable();
            $table->string('cam')->nullable();
            $table->string('ram')->nullable();
            $table->string('lm')->nullable();
            $table->string('lcm')->nullable();
            $table->string('cm')->nullable();
            $table->string('rcm')->nullable();
            $table->string('rm')->nullable();
            $table->string('lwb')->nullable();
            $table->string('ldm')->nullable();
            $table->string('cdm')->nullable();
            $table->string('rdm')->nullable();
            $table->string('rwb')->nullable();
            $table->string('lb')->nullable();
            $table->string('lcb')->nullable();
            $table->string('cb')->nullable();
            $table->string('rcb')->nullable();
            $table->string('rb')->nullable();
            $table->string('gk')->nullable();
            $table->string('player_face_url')->nullable();
            $table->timestamps();
            $table->unique(['jogo_id', 'long_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('elencopadrao');
    }
};
