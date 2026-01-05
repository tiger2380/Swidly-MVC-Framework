<?php

declare(strict_types=1);

namespace Swidly\Core\Services;

use Swidly\Core\Swidly;

/**
 * AIService
 * 
 * Handles AI-powered itinerary generation using OpenAI or Claude API
 * Requires API key to be configured in config.php
 */
class AIService
{
    private string $apiKey;
    private string $model;
    private string $apiEndpoint;

    public function __construct()
    {
        // Get AI configuration from config
        $this->apiKey = Swidly::getConfig('ai_api_key') ?? '';
        $this->model = Swidly::getConfig('ai_model') ?? 'gpt-4';
        $this->apiEndpoint = 'https://api.openai.com/v1/chat/completions';
    }

    /**
     * Generate itinerary based on user preferences
     * 
     * @param array $params Itinerary parameters
     * @return array Generated itinerary data
     * @throws \Exception
     */
    public function generateItinerary(array $params): array
    {
        if (empty($this->apiKey)) {
            throw new \Exception('AI API key not configured. Please add "ai_api_key" to config.php');
        }

        $prompt = $this->buildPrompt($params);
        $response = $this->callAI($prompt);
        
        return $this->parseAIResponse($response, $params);
    }

    /**
     * Build AI prompt from user parameters
     * 
     * @param array $params
     * @return string
     */
    private function buildPrompt(array $params): string
    {
        $destination = $params['destination'] ?? 'Unknown';
        $days = $params['days'] ?? 3;
        $budget = $params['budget'] ?? 'moderate';
        $travelStyle = $params['travel_style'] ?? 'balanced';
        $interests = $params['interests'] ?? [];
        
        $prompt = "Create a detailed {$days}-day travel itinerary for {$destination}.\n\n";
        $prompt .= "Travel Style: {$travelStyle}\n";
        $prompt .= "Budget: {$budget}\n";
        
        if (!empty($interests)) {
            $prompt .= "Interests: " . implode(', ', $interests) . "\n";
        }

        $prompt .= "\nPlease provide a day-by-day itinerary with the following for each activity:\n";
        $prompt .= "- Time slot (morning/afternoon/evening/night)\n";
        $prompt .= "- Activity title\n";
        $prompt .= "- Detailed description\n";
        $prompt .= "- Location name and address (if applicable)\n";
        $prompt .= "- Activity type (dining/sightseeing/shopping/entertainment/nature/beach/adventure/relaxation)\n";
        $prompt .= "- Estimated cost in USD\n";
        $prompt .= "- Helpful tips or notes\n\n";
        $prompt .= "Format the response as JSON with this structure:\n";
        $prompt .= "{\n";
        $prompt .= '  "title": "Trip title",\n';
        $prompt .= '  "description": "Overview of the trip",\n';
        $prompt .= '  "days": [\n';
        $prompt .= '    {\n';
        $prompt .= '      "day_number": 1,\n';
        $prompt .= '      "activities": [\n';
        $prompt .= '        {\n';
        $prompt .= '          "time_slot": "morning",\n';
        $prompt .= '          "title": "Activity name",\n';
        $prompt .= '          "description": "Details",\n';
        $prompt .= '          "activity_type": "sightseeing",\n';
        $prompt .= '          "location_name": "Place name",\n';
        $prompt .= '          "location_address": "Full address",\n';
        $prompt .= '          "estimated_cost": 25.00,\n';
        $prompt .= '          "notes": "Tips"\n';
        $prompt .= '        }\n';
        $prompt .= '      ]\n';
        $prompt .= '    }\n';
        $prompt .= '  ]\n';
        $prompt .= "}\n";

        return $prompt;
    }

    /**
     * Call OpenAI API
     * 
     * @param string $prompt
     * @return string JSON response
     * @throws \Exception
     */
    private function callAI(string $prompt): string
    {
        $data = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert travel planner. Create detailed, practical, and enjoyable itineraries. Always respond with valid JSON.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 3000
        ];

        $ch = curl_init($this->apiEndpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error']['message'] ?? 'Unknown API error';
            throw new \Exception("AI API Error ({$httpCode}): {$errorMessage}");
        }

        $responseData = json_decode($response, true);
        
        if (!isset($responseData['choices'][0]['message']['content'])) {
            throw new \Exception('Invalid response from AI API');
        }

        return $responseData['choices'][0]['message']['content'];
    }

    /**
     * Parse AI response into structured data
     * 
     * @param string $aiResponse
     * @param array $params Original parameters
     * @return array Structured itinerary data
     */
    private function parseAIResponse(string $aiResponse, array $params): array
    {
        // Extract JSON from response (AI might include markdown code blocks)
        $aiResponse = trim($aiResponse);
        if (str_starts_with($aiResponse, '```json')) {
            $aiResponse = preg_replace('/^```json\s*/s', '', $aiResponse);
            $aiResponse = preg_replace('/\s*```$/s', '', $aiResponse);
        } elseif (str_starts_with($aiResponse, '```')) {
            $aiResponse = preg_replace('/^```\s*/s', '', $aiResponse);
            $aiResponse = preg_replace('/\s*```$/s', '', $aiResponse);
        }

        $data = json_decode($aiResponse, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Failed to parse AI response: ' . json_last_error_msg());
        }

        // Validate required structure
        if (!isset($data['days']) || !is_array($data['days'])) {
            throw new \Exception('Invalid itinerary structure from AI');
        }

        // Add metadata
        $data['ai_generated'] = true;
        $data['destination'] = $params['destination'];
        $data['travel_style'] = $params['travel_style'] ?? null;
        $data['budget'] = $params['budget'] ?? null;

        return $data;
    }

    /**
     * Get suggestions for destinations based on user preferences
     * 
     * @param array $preferences User preferences
     * @return array List of suggested destinations
     */
    public function suggestDestinations(array $preferences): array
    {
        if (empty($this->apiKey)) {
            // Return default suggestions if AI not configured
            return [
                'Paris, France',
                'Tokyo, Japan',
                'New York, USA',
                'Barcelona, Spain',
                'Bali, Indonesia'
            ];
        }

        $interests = $preferences['interests'] ?? [];
        $budget = $preferences['budget'] ?? 'moderate';
        $travelStyle = $preferences['travel_style'] ?? 'balanced';

        $prompt = "Suggest 5 travel destinations based on these preferences:\n";
        $prompt .= "Budget: {$budget}\n";
        $prompt .= "Travel Style: {$travelStyle}\n";
        if (!empty($interests)) {
            $prompt .= "Interests: " . implode(', ', $interests) . "\n";
        }
        $prompt .= "\nRespond with only a JSON array of destination names with country, like: [\"Paris, France\", \"Tokyo, Japan\"]";

        try {
            $response = $this->callAI($prompt);
            $destinations = json_decode($response, true);
            
            if (is_array($destinations)) {
                return $destinations;
            }
        } catch (\Exception $e) {
            error_log('AI destination suggestion failed: ' . $e->getMessage());
        }

        // Fallback to defaults
        return [
            'Paris, France',
            'Tokyo, Japan',
            'New York, USA',
            'Barcelona, Spain',
            'Bali, Indonesia'
        ];
    }
}
