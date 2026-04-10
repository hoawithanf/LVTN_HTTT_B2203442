<?php

require_once __DIR__ . '/meaning_api_openai.php';

function analyzeSongMeaning_Gemini($lyrics)
{
    return analyzeSongMeaning_OpenAI($lyrics);
}
