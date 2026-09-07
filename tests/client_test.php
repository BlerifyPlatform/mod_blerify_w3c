<?php

namespace mod_blerify;

defined('MOODLE_INTERNAL') || die();

class client_test extends \advanced_testcase {

    private function host_allowed(string $host): bool {
        $method = new \ReflectionMethod(\mod_blerify\client\client::class, 'host_allowed');
        $method->setAccessible(true);

        return (bool) $method->invoke(null, $host);
    }

    public function test_allows_blerify_hosts(): void {
        $this->assertTrue($this->host_allowed('blerify.com'));
        $this->assertTrue($this->host_allowed('api.blerify.com'));
        $this->assertTrue($this->host_allowed('api.demo.blerify.com'));
        $this->assertTrue($this->host_allowed('api.staging.blerify.com'));
    }

    /** @dataProvider bypass_provider */
    public function test_rejects_lookalike_hosts(string $host): void {
        $this->assertFalse($this->host_allowed($host));
    }

    public static function bypass_provider(): array {
        return [
            'suffix injection' => ['blerify.com.evil.com'],
            'prefix injection' => ['notblerify.com'],
            'unrelated domain' => ['evil.com'],
            'loopback' => ['127.0.0.1'],
            'internal name' => ['localhost'],
            'empty host' => [''],
            'metadata service' => ['169.254.169.254'],
        ];
    }

    public function test_allowlist_is_case_insensitive(): void {
        $this->assertTrue($this->host_allowed('API.BLERIFY.COM'));
        $this->assertFalse($this->host_allowed('EVIL.COM'));
    }

    public function test_construction_requires_configuration(): void {
        $this->resetAfterTest();

        unset_config('service_account_json', 'mod_blerify');

        $this->expectException(\moodle_exception::class);
        new \mod_blerify\client\client();
    }
}
