<?php

defined('MOODLE_INTERNAL') || die();

class mod_blerify_generator extends testing_module_generator {

    public function create_instance($record = null, ?array $options = null) {
        $record = (object) (array) $record;

        $defaults = [
            'name' => 'Blerify certificate',
            'projectid' => '7d30b43d-2be7-47b3-99af-a6628f41f4cd',
            'templateid' => '17b60a9e-e404-4185-9a2d-9ac590a72b1e',
            'templatename' => 'Test template',
            'completionissue' => 1,
            'passgrade' => 70,
        ];

        foreach ($defaults as $field => $value) {
            if (!isset($record->{$field})) {
                $record->{$field} = $value;
            }
        }

        return parent::create_instance($record, (array) $options);
    }
}
