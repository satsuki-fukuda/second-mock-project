<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrectionBreak extends Model
{
    use HasFactory;

        protected $fillable = [
        'correction_request_id',
        'new_break_start',
        'new_break_end'
    ];
        /**
     * 親の申請データへのリレーション
     */
    public function correctionRequest()
    {
        return $this->belongsTo(CorrectionRequest::class);
    }

}
