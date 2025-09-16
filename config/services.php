<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Services Configuration
    |--------------------------------------------------------------------------
    */

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('OPENAI_MODEL', 'gpt-4'),
        'max_tokens' => env('OPENAI_MAX_TOKENS', 2000),
        'temperature' => env('OPENAI_TEMPERATURE', 0.3),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-3-sonnet-20240229'),
        'max_tokens' => env('ANTHROPIC_MAX_TOKENS', 4096),
        'temperature' => env('ANTHROPIC_TEMPERATURE', 0.3),
    ],

    'lexisnexis' => [
        'api_key' => env('LEXISNEXIS_API_KEY'),
        'base_url' => env('LEXISNEXIS_BASE_URL', 'https://api.lexisnexis.com'),
        'timeout' => env('LEXISNEXIS_TIMEOUT', 30),
    ],

    'legal_ai_library' => [
        'api_key' => env('LEGAL_AI_LIBRARY_API_KEY'),
        'base_url' => env('LEGAL_AI_LIBRARY_BASE_URL', 'https://api.legal-ai-library.com'),
        'timeout' => env('LEGAL_AI_LIBRARY_TIMEOUT', 30),
    ],

    'lexpredict' => [
        'api_key' => env('LEXPREDICT_API_KEY'),
        'base_url' => env('LEXPREDICT_BASE_URL', 'https://api.lexpredict.com'),
        'timeout' => env('LEXPREDICT_TIMEOUT', 30),
    ],

    'ravel_law' => [
        'api_key' => env('RAVEL_LAW_API_KEY'),
        'base_url' => env('RAVEL_LAW_BASE_URL', 'https://api.ravel.law'),
        'timeout' => env('RAVEL_LAW_TIMEOUT', 30),
    ],

    'google_gemini' => [
        'api_key' => env('GOOGLE_GEMINI_API_KEY'),
        'model' => env('GOOGLE_GEMINI_MODEL', 'gemini-pro'),
        'max_tokens' => env('GOOGLE_GEMINI_MAX_TOKENS', 2048),
        'temperature' => env('GOOGLE_GEMINI_TEMPERATURE', 0.3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Voice Services Configuration
    |--------------------------------------------------------------------------
    */

    'openai_tts' => [
        'api_key' => env('OPENAI_API_KEY'), // Usa la misma clave de OpenAI
        'model' => env('OPENAI_TTS_MODEL', 'tts-1-hd'), // Modelo de alta calidad
        'voice' => env('OPENAI_TTS_VOICE', 'alloy'), // Voz clara y profesional
        'format' => env('OPENAI_TTS_FORMAT', 'mp3'),
        'speed' => env('OPENAI_TTS_SPEED', 1.0),
    ],

    'elevenlabs' => [
        'api_key' => env('ELEVENLABS_API_KEY'),
        'base_url' => env('ELEVENLABS_BASE_URL', 'https://api.elevenlabs.io/v1'),
        'model' => env('ELEVENLABS_MODEL', 'eleven_monolingual_v1'),
        'voice_id' => env('ELEVENLABS_VOICE_ID', '21m00Tcm4TlvDq8ikWAM'), // Rachel - voz española natural
        'stability' => env('ELEVENLABS_STABILITY', 0.75),
        'similarity_boost' => env('ELEVENLABS_SIMILARITY_BOOST', 0.8),
        'style' => env('ELEVENLABS_STYLE', 0.0),
        'use_speaker_boost' => env('ELEVENLABS_SPEAKER_BOOST', true),
    ],

    'google_cloud' => [
        'project_id' => env('GOOGLE_CLOUD_PROJECT_ID'),
        'key_file_path' => env('GOOGLE_CLOUD_KEY_FILE'),
        'tts_language_code' => env('GOOGLE_TTS_LANGUAGE_CODE', 'es-ES'),
        'tts_voice_name' => env('GOOGLE_TTS_VOICE_NAME', 'es-ES-Neural2-A'),
        'tts_audio_encoding' => env('GOOGLE_TTS_AUDIO_ENCODING', 'MP3'),
        'stt_language_code' => env('GOOGLE_STT_LANGUAGE_CODE', 'es-ES'),
        'stt_encoding' => env('GOOGLE_STT_ENCODING', 'LINEAR16'),
        'stt_sample_rate_hertz' => env('GOOGLE_STT_SAMPLE_RATE', 16000),
    ],

    'azure_cognitive' => [
        'api_key' => env('AZURE_SPEECH_API_KEY'),
        'region' => env('AZURE_SPEECH_REGION', 'eastus'),
        'tts_voice_name' => env('AZURE_TTS_VOICE', 'es-ES-ElviraNeural'),
        'tts_style' => env('AZURE_TTS_STYLE', 'professional'),
        'stt_language' => env('AZURE_STT_LANGUAGE', 'es-ES'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Voice Optimization Configuration
    |--------------------------------------------------------------------------
    */

    'voice_optimization' => [
        'max_audio_duration' => env('VOICE_MAX_DURATION', 30), // segundos
        'optimal_sample_rate' => env('VOICE_OPTIMAL_SAMPLE_RATE', 16000), // Hz
        'audio_quality' => env('VOICE_AUDIO_QUALITY', 16), // bits
        'compression_format' => env('VOICE_COMPRESSION_FORMAT', 'mp3'),
        'cache_duration' => env('VOICE_CACHE_DURATION', 7200), // 2 horas
        'enable_noise_reduction' => env('VOICE_NOISE_REDUCTION', true),
        'auto_update_models' => env('VOICE_AUTO_UPDATE_MODELS', true),
        'fallback_enabled' => env('VOICE_FALLBACK_ENABLED', true),
    ],

];
