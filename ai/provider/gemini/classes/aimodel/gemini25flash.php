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

namespace aiprovider_gemini\aimodel;

use core_ai\aimodel\base;
class gemini25flash extends base implements gemini_base {
    public function get_model_name(): string {
        return 'gemini-2.5-flash';
    }

    public function get_model_display_name(): string {
        return 'Gemini 2.5 Flash';
    }

    public function has_model_settings(): bool {
        return true;
    }

    public function get_model_settings(): array {
        return gemini_base::MODEL_TYPE_SETTING;
    }

    public function model_type(): array {
        return [gemini_base::MODEL_TYPE_TEXT];
    }
}