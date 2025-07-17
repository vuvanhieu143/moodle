<?php
namespace aiprovider_gemini\aimodel;

use core_ai\aimodel\base;

class gemini20image extends base implements gemini_base {
    public function get_model_name(): string {
        return 'gemini-2.0-flash-preview-image-generation';
    }
    public function get_model_display_name(): string {
        return 'Gemini Image‑Gen 2.0 Flash';
    }
    public function has_model_settings(): bool {
        return true;
    }
    public function get_model_settings(): array {
        return gemini_base::MODEL_TYPE_SETTING;
    }
    public function model_type(): array {
        return [self::MODEL_TYPE_IMAGE];
    }
}