<?php

namespace mod_blerify;

use mod_blerify\local\credentials;

defined('MOODLE_INTERNAL') || die();

class credentials_test extends \advanced_testcase {

    private $course;

    private $blerify;

    private $user;

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        $this->course = $this->getDataGenerator()->create_course();
        $this->blerify = $this->getDataGenerator()->create_module('blerify',
            ['course' => $this->course->id]);
        $this->user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id);
    }

    private function make_credential(string $status, array $extra = []): \stdClass {
        global $DB;

        $record = (object) array_merge([
            'blerifyid' => $this->blerify->id,
            'userid' => $this->user->id,
            'credentialid' => '0xabc',
            'projectid' => $this->blerify->projectid,
            'templateid' => $this->blerify->templateid,
            'status' => $status,
            'remotestatus' => 'PENDING',
            'code' => null,
            'errordetail' => null,
            'timeissued' => 0,
            'timeclaimed' => 0,
            'timecreated' => time(),
            'timemodified' => time(),
        ], $extra);

        $record->id = $DB->insert_record('blerify_credentials', $record);

        return $record;
    }

    private function apply(\stdClass $record, array $result): \stdClass {
        $manager = new credentials();
        $method = new \ReflectionMethod($manager, 'apply_poll_result');
        $method->setAccessible(true);

        return $method->invoke($manager, $record, $result);
    }

    public function test_pending_keeps_issuing(): void {
        $record = $this->make_credential(credentials::STATUS_ISSUING);

        $updated = $this->apply($record, ['status' => 'PENDING', 'code' => null]);

        $this->assertSame(credentials::STATUS_ISSUING, $updated->status);
        $this->assertEquals(0, $updated->timeissued);
    }

    public function test_sent_becomes_issued_with_code(): void {
        global $DB;

        $record = $this->make_credential(credentials::STATUS_ISSUING);

        $updated = $this->apply($record, ['status' => 'SENT', 'code' => 'ABC123']);

        $this->assertSame(credentials::STATUS_ISSUED, $updated->status);
        $this->assertSame('ABC123', $updated->code);
        $this->assertGreaterThan(0, $updated->timeissued);

        $stored = $DB->get_record('blerify_credentials', ['id' => $record->id]);
        $this->assertSame(credentials::STATUS_ISSUED, $stored->status);
        $this->assertSame('ABC123', $stored->code);
    }

    public function test_delivered_becomes_claimed(): void {
        $record = $this->make_credential(credentials::STATUS_ISSUED,
            ['code' => 'ABC123', 'remotestatus' => 'SENT', 'timeissued' => 100]);

        $updated = $this->apply($record, ['status' => 'DELIVERED', 'code' => null]);

        $this->assertSame(credentials::STATUS_CLAIMED, $updated->status);
        $this->assertGreaterThan(0, $updated->timeclaimed);
        $this->assertEquals(100, $updated->timeissued);
    }

    public function test_timeissued_is_not_overwritten(): void {
        $record = $this->make_credential(credentials::STATUS_ISSUING);

        $first = $this->apply($record, ['status' => 'SENT', 'code' => 'ABC123']);
        $issued = $first->timeissued;

        $second = $this->apply($first, ['status' => 'DELIVERED', 'code' => null]);

        $this->assertEquals($issued, $second->timeissued);
    }

    public function test_unchanged_result_does_not_touch_the_record(): void {
        global $DB;

        $record = $this->make_credential(credentials::STATUS_ISSUED,
            ['code' => 'ABC123', 'remotestatus' => 'SENT', 'timeissued' => 100]);
        $DB->set_field('blerify_credentials', 'timemodified', 500, ['id' => $record->id]);
        $record->timemodified = 500;

        $this->apply($record, ['status' => 'SENT', 'code' => 'ABC123']);

        $stored = $DB->get_record('blerify_credentials', ['id' => $record->id]);
        $this->assertEquals(500, $stored->timemodified);
    }

    public function test_scope_prefers_the_stored_pair(): void {
        $record = $this->make_credential(credentials::STATUS_ISSUED,
            ['projectid' => 'p-original', 'templateid' => 't-original']);

        $manager = new credentials();
        $method = new \ReflectionMethod($manager, 'get_scope');
        $method->setAccessible(true);

        $this->assertSame('p-original', $method->invoke($manager, $record, 'projectid'));
        $this->assertSame('t-original', $method->invoke($manager, $record, 'templateid'));
    }

    public function test_scope_falls_back_to_the_activity(): void {
        $record = $this->make_credential(credentials::STATUS_ISSUED,
            ['projectid' => '', 'templateid' => '']);

        $manager = new credentials();
        $method = new \ReflectionMethod($manager, 'get_scope');
        $method->setAccessible(true);

        $this->assertSame($this->blerify->projectid, $method->invoke($manager, $record, 'projectid'));
        $this->assertSame($this->blerify->templateid, $method->invoke($manager, $record, 'templateid'));
    }

    public function test_one_credential_per_user_and_activity(): void {
        $this->make_credential(credentials::STATUS_ISSUED);

        $this->expectException(\dml_exception::class);
        $this->make_credential(credentials::STATUS_PENDING);
    }

    public function test_lookup_is_scoped_to_the_learner(): void {
        $this->make_credential(credentials::STATUS_ISSUED);
        $other = $this->getDataGenerator()->create_user();

        $manager = new credentials();

        $this->assertNotFalse($manager->get_credential_for_user($this->blerify->id, $this->user->id));
        $this->assertFalse($manager->get_credential_for_user($this->blerify->id, $other->id));
    }

    public function test_issuing_without_a_project_fails(): void {
        $record = (object) ['id' => $this->blerify->id, 'course' => $this->course->id, 'projectid' => ''];

        $manager = new credentials();
        $method = new \ReflectionMethod($manager, 'get_project_id');
        $method->setAccessible(true);

        $this->expectException(\moodle_exception::class);
        $method->invoke($manager, $record);
    }
}
