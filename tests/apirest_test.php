<?php

namespace mod_blerify;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/fake_client.php');

class apirest_test extends \advanced_testcase {

    public function test_get_projects_keeps_only_issuing_roles(): void {
        $client = new blerify_fake_client();
        $client->responses[] = json_decode(json_encode([
            ['name' => 'credentials.api', 'projectId' => 'p-1', 'projectName' => 'Uno'],
            ['name' => 'verifications.api', 'projectId' => 'p-2', 'projectName' => 'Dos'],
            ['name' => 'notifications.api', 'projectId' => null, 'projectName' => null],
        ]));

        $projects = (new \mod_blerify\apirest\apirest($client))->get_projects();

        $this->assertCount(1, $projects);
        $this->assertSame('p-1', $projects[0]['id']);
        $this->assertSame('Uno', $projects[0]['name']);
    }

    public function test_get_projects_deduplicates(): void {
        $client = new blerify_fake_client();
        $client->responses[] = json_decode(json_encode([
            ['name' => 'credentials.api', 'projectId' => 'p-1', 'projectName' => 'Uno'],
            ['name' => 'credentials.api', 'projectId' => 'p-1', 'projectName' => 'Uno'],
        ]));

        $this->assertCount(1, (new \mod_blerify\apirest\apirest($client))->get_projects());
    }

    public function test_get_projects_tolerates_malformed_payload(): void {
        $client = new blerify_fake_client();
        $client->responses[] = null;

        $this->assertSame([], (new \mod_blerify\apirest\apirest($client))->get_projects());
    }

    public function test_get_templates_normalises_missing_image(): void {
        $client = new blerify_fake_client();
        $client->responses[] = json_decode(json_encode([
            'templates' => [
                ['id' => 't-1', 'title' => 'Con imagen', 'image' => 'https://cdn.example/x.png'],
                ['id' => 't-2', 'title' => 'Sin imagen', 'image' => 'NO_IMAGE'],
            ],
        ]));

        $templates = (new \mod_blerify\apirest\apirest($client))->get_templates('p-1');

        $this->assertCount(2, $templates);
        $this->assertSame('https://cdn.example/x.png', $templates[0]['image']);
        $this->assertSame('', $templates[1]['image']);
    }

    public function test_create_credential_sends_email_only(): void {
        $this->resetAfterTest();

        $client = new blerify_fake_client();
        $client->responses[] = json_decode(json_encode(['_id' => '0xabc']));

        $user = $this->getDataGenerator()->create_user([
            'email' => 'alumno@example.com',
            'firstname' => 'Ana',
            'lastname' => 'Perez',
        ]);

        $id = (new \mod_blerify\apirest\apirest($client))
            ->create_credential($user, 't-1', 'p-1');

        $this->assertSame('0xabc', $id);

        [$method, $path, $body] = $client->calls[0];
        $this->assertSame('POST', $method);
        $this->assertStringContainsString('/projects/p-1/credentials', $path);
        $this->assertSame('alumno@example.com', $body['organizationUser']['email']);
        $this->assertArrayNotHasKey('did', $body['organizationUser']);
    }

    public function test_create_credential_rejects_malformed_response(): void {
        $this->resetAfterTest();

        $client = new blerify_fake_client();
        $client->responses[] = json_decode(json_encode(['unexpected' => true]));
        $user = $this->getDataGenerator()->create_user();

        $this->expectException(\Exception::class);
        (new \mod_blerify\apirest\apirest($client))->create_credential($user, 't-1', 'p-1');
    }

    public function test_approve_falls_back_to_sign(): void {
        $this->resetAfterTest();

        $client = new blerify_fake_client();
        $client->failures['/approve'] = 'Blerify API error (404)';
        $client->responses[] = 'success';

        (new \mod_blerify\apirest\apirest($client))
            ->approve_credential('p-1', '0xabc', 't-1');

        $this->assertDebuggingCalled();
        $this->assertCount(2, $client->calls);
        $this->assertStringContainsString('/approve', $client->calls[0][1]);
        $this->assertStringContainsString('/sign', $client->calls[1][1]);
    }

    public function test_poll_credential_extracts_deliverables(): void {
        $client = new blerify_fake_client();
        $client->responses[] = json_decode(json_encode([
            'status' => 'SENT',
            'code' => 'GJL7kiSmvFpb',
            'pdf' => 'https://storage.googleapis.com/bucket/a.pdf?sig=1',
            'thumbnail' => 'https://storage.googleapis.com/bucket/a.png?sig=1',
        ]));

        $result = (new \mod_blerify\apirest\apirest($client))
            ->poll_credential('p-1', '0xabc', 't-1');

        $this->assertSame('SENT', $result['status']);
        $this->assertSame('GJL7kiSmvFpb', $result['code']);
        $this->assertStringStartsWith('https://', $result['pdf']);
        $this->assertStringStartsWith('https://', $result['thumbnail']);
        $this->assertStringContainsString('templateId=t-1', $client->calls[0][1]);
    }

    public function test_poll_credential_while_pending(): void {
        $client = new blerify_fake_client();
        $client->responses[] = json_decode(json_encode([
            'status' => 'PENDING', 'code' => null, 'pdf' => null, 'thumbnail' => null,
        ]));

        $result = (new \mod_blerify\apirest\apirest($client))
            ->poll_credential('p-1', '0xabc', 't-1');

        $this->assertSame('PENDING', $result['status']);
        $this->assertNull($result['code']);
        $this->assertNull($result['pdf']);
    }
}
