<?php

function nln_openai_api_key()
{
    return trim((string) getenv('OPENAI_API_KEY'));
}

function nln_openai_model($default = 'gpt-4.1-mini')
{
    $model = trim((string) getenv('OPENAI_MODEL'));
    return $model !== '' ? $model : $default;
}

function nln_openai_extract_text($data)
{
    if (!is_array($data)) {
        return '';
    }

    if (!empty($data['output_text']) && is_string($data['output_text'])) {
        return trim($data['output_text']);
    }

    if (!empty($data['output']) && is_array($data['output'])) {
        foreach ($data['output'] as $item) {
            if (!empty($item['content']) && is_array($item['content'])) {
                foreach ($item['content'] as $content) {
                    if (!empty($content['text']) && is_string($content['text'])) {
                        return trim($content['text']);
                    }
                }
            }
        }
    }

    return '';
}

function nln_openai_text_response($instructions, $input, $options = [])
{
    $apiKey = nln_openai_api_key();
    if ($apiKey === '') {
        return ['success' => false, 'error' => 'Thiếu OPENAI_API_KEY trong .env'];
    }

    $payload = [
        'model' => $options['model'] ?? nln_openai_model(),
        'instructions' => $instructions,
        'input' => $input,
    ];

    if (!empty($options['max_output_tokens'])) {
        $payload['max_output_tokens'] = (int) $options['max_output_tokens'];
    }

    $ch = curl_init('https://api.openai.com/v1/responses');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError) {
        return ['success' => false, 'error' => 'OpenAI CURL error: ' . $curlError];
    }

    $data = json_decode((string) $response, true);
    if (!is_array($data)) {
        return ['success' => false, 'error' => 'OpenAI trả về dữ liệu không hợp lệ'];
    }

    if ($httpCode >= 400 || !empty($data['error']['message'])) {
        $message = $data['error']['message'] ?? ('OpenAI request failed with HTTP ' . $httpCode);
        return ['success' => false, 'error' => $message];
    }

    $text = nln_openai_extract_text($data);
    if ($text === '') {
        return ['success' => false, 'error' => 'OpenAI không trả về nội dung text'];
    }

    return [
        'success' => true,
        'text' => $text,
        'model' => $payload['model'],
    ];
}
