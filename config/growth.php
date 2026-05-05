<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Pulse — heuristic speed score (0–100) until PageSpeed API is wired
    |--------------------------------------------------------------------------
    */
    'ai_pulse_speed_baseline' => (int) env('AI_PULSE_SPEED_BASELINE', 72),
];
