<?php

namespace mod_blerify;

defined('MOODLE_INTERNAL') || die();

class blerify_fake_client {

    public $responses = [];

    public $calls = [];

    public $failures = [];

    public function get_organization_id() {
        return 'org-1111';
    }

    public function get($path) {
        return $this->record('GET', $path, null);
    }

    public function post($path, $data = []) {
        return $this->record('POST', $path, $data);
    }

    public function put($path, $data = []) {
        return $this->record('PUT', $path, $data);
    }

    private function record($method, $path, $data) {
        $this->calls[] = [$method, $path, $data];

        foreach ($this->failures as $fragment => $message) {
            if (strpos($path, $fragment) !== false) {
                throw new \Exception($message);
            }
        }

        return array_shift($this->responses);
    }
}
