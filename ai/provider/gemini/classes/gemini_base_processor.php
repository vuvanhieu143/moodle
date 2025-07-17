<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace aiprovider_gemini;

use core\http_client;
use core_ai\process_base;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\UriInterface;

/**
 * Base class for Gemini AI provider processors.
 *
 * This class provides common functionality for processing requests to the Gemini AI API.
 *
 * @package    aiprovider_gemini
 * @copyright  2025 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class gemini_base_processor extends process_base {
    protected function get_endpoint(string $model): string {
        return new Uri($this->provider->actionconfig[$this->action::class]['settings']['endpoint']);
    }

    /**
     * Get the name of the model to use.
     *
     * @return string
     */
    protected function get_model(): string {
        return $this->provider->actionconfig[$this->action::class]['settings']['model'];
    }

    public function query_ai_api(): object {
        $request = $this->create_request_object(
                userid: $this->provider->generate_userid($this->action->get_configuration('userid')),
        );
        $request = $this->provider->add_authentication_headers($request);

        $client = \core\di::get(http_client::class);
        try {
            // Call the external AI service.
            $response = $client->send($request, [
                    'base_uri' => $this->get_endpoint() . '/' . $this->get_model() . ':generateContent',
                    RequestOptions::HTTP_ERRORS => false,
            ]);
        } catch (RequestException $e) {
            // Handle any exceptions.
            return \core_ai\error\factory::create($e->getCode(), $e->getMessage())->get_error_details();
        }

        // Double-check the response codes, in case of a non 200 that didn't throw an error.
        $status = $response->getStatusCode();
        if ($status === 200) {
            return $this->handle_api_success($response);
        } else {
            return $this->handle_api_error($response);
        }
    }

    /**
     * Get the model settings.
     *
     * @return array
     */
    protected function get_model_settings(): array {
        $settings = $this->provider->actionconfig[$this->action::class]['settings'];
        if (!empty($settings['modelextraparams'])) {
            // Custom model settings.
            $params = json_decode($settings['modelextraparams'], true);
            foreach ($params as $key => $param) {
                $settings[$key] = $param;
            }
        }

        // Unset unnecessary settings.
        unset(
                $settings['model'],
                $settings['endpoint'],
                $settings['systeminstruction'],
        );
        return $settings;
    }

    /**
     * Get the system instructions.
     *
     * @return string
     */
    protected function get_system_instruction(): string {
        return $this->action::get_system_instruction();
    }

    protected function build_generation_config(array $config): array {
        return array_filter([
                'temperature'        => (float)($config['temperature'] ?? 1.0),
                'topP'               => (float)($config['top_p'] ?? 1.0),
                'topK'               => isset($config['top_k']) ? (int)$config['top_k'] : null,
                'presencePenalty'    => (float)($config['presence_penalty'] ?? 0.0),
                'frequencyPenalty'   => (float)($config['frequency_penalty'] ?? 0.0),
                'maxOutputTokens'    => isset($config['max_output_tokens']) ? (int)$config['max_output_tokens'] : null,
                'stopSequences'      => $config['stop_sequences'] ?? null,
        ], fn($v) => $v !== null);
    }

    /**
     * Determine modalities for the current AI action.
     *
     * @return string[]
     */
    protected function get_modalities_for_model(string $model): array {
        // If the model supports image generation
        if (stripos($model, 'image-generation') !== false) {
            return ['TEXT', 'IMAGE'];
        }
        // Otherwise, default to text only
        return ['TEXT'];
    }
}
