<?php
// This file is part of the Blerify Certificate module for Moodle - http://moodle.org/
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

/**
 * Blerify W3C credential API rest client.
 * Implements the service-account flow: Create -> Approve -> Poll.
 *
 * @package    mod_blerify
 * @copyright  Blerify <dev@blerify.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_blerify\apirest;

defined('MOODLE_INTERNAL') || die();

use mod_blerify\client\client;

class apirest {

    /** @var client */
    private $client;

    /** @var string */
    private $organizationid;

    /**
     * Constructor.
     *
     * @param client|null $client Blerify client (null creates default).
     */
    public function __construct($client = null) {
        $this->client = $client ? $client : new client();
        $this->organizationid = $this->client->get_organization_id();
    }

    /**
     * Base path for every organization + project scoped call.
     *
     * @param string $projectid
     * @return string
     */
    private function project_path($projectid, $organizationid = null) {
        $org = !empty($organizationid) ? $organizationid : $this->organizationid;

        return '/api/v1/organizations/' . rawurlencode((string) $org) .
               '/projects/' . rawurlencode((string) $projectid);
    }

    public function get_organization_id() {
        return $this->organizationid;
    }

    /**
     * The projects this service account can issue credentials under.
     *
     * The roles endpoint reports what the token itself is allowed to do, so
     * filtering by the issuing role keeps projects the account could not use
     * out of the list instead of letting them fail later with a 403.
     *
     * @return array List of ['id' => string, 'name' => string], one per project.
     * @throws \Exception On API error.
     */
    public function get_projects() {
        $response = $this->client->get('/api/v1/iam/serviceAccounts/me/roles');

        if (!$response || !is_array($response)) {
            return [];
        }

        $projects = [];
        foreach ($response as $role) {
            if (empty($role->projectId) || ($role->name ?? '') !== 'credentials.api') {
                continue;
            }
            $projects[$role->projectId] = !empty($role->projectName)
                ? $role->projectName
                : $role->projectId;
        }

        $list = [];
        foreach ($projects as $id => $name) {
            $list[] = ['id' => $id, 'name' => $name];
        }

        return $list;
    }

    /**
     * List the credential templates available in a project.
     *
     * @param string $projectid Blerify project UUID.
     * @return array List of ['id' => string, 'title' => string, 'description' => string, 'image' => string].
     * @throws \Exception On API error.
     */
    public function get_templates($projectid) {
        $response = $this->client->get($this->project_path($projectid) . '/templates/summary');

        if (!$response || !isset($response->templates) || !is_array($response->templates)) {
            return [];
        }

        $templates = [];
        foreach ($response->templates as $template) {
            if (empty($template->id)) {
                continue;
            }
            $templates[] = [
                'id' => $template->id,
                'title' => isset($template->title) ? $template->title : $template->id,
                'description' => isset($template->description) ? $template->description : '',
                'image' => (isset($template->image) && $template->image !== 'NO_IMAGE')
                    ? $template->image : '',
            ];
        }

        return $templates;
    }

    /**
     * Step 1: Create a W3C credential for a receiver identified by email.
     *
     * The receiver does not need a wallet DID: Blerify creates/updates the
     * organization user from the email and the credential is claimed later.
     *
     * @param object $user Moodle user object.
     * @param string $templateid Blerify template UUID.
     * @param string $projectid Blerify project UUID.
     * @param array $w3cdata Extra fields rendered into the credential.
     * @return string The created credential id.
     * @throws \Exception On API error.
     */
    public function create_credential($user, $templateid, $projectid, array $w3cdata = []) {
        $body = [
            'templateId' => $templateid,
            'additionalData' => [
                'w3cData' => $w3cdata + [
                    'email' => $user->email,
                    'fullname' => fullname($user),
                    'name' => $user->firstname,
                    'lastname' => $user->lastname,
                ],
            ],
            'organizationUser' => [
                'email' => $user->email,
            ],
            'options' => [
                'omitApproval' => false,
                'approvers' => true,
            ],
        ];

        $response = $this->client->post($this->project_path($projectid) . '/credentials', $body);
        if (!$response) {
            throw new \Exception('Failed to create credential: empty response');
        }

        // The API has returned both a bare credential and a {credential: {...}} envelope.
        $credential = isset($response->credential) ? $response->credential : $response;
        if (empty($credential->_id)) {
            throw new \Exception('Failed to create credential: malformed response');
        }

        return $credential->_id;
    }

    /**
     * Step 2: Approve the credential as the service account, which triggers issuance.
     *
     * Issuance runs asynchronously: a successful call only means the approval was
     * recorded, so the result must be read with poll_credential().
     *
     * The integration guide documents this on /sign while the API collection
     * documents the same body and response on /approve, so /approve is tried
     * first and /sign is used as a fallback.
     *
     * @param string $projectid
     * @param string $credentialid
     * @param string $templateid
     * @param string $lang Receiver language, e.g. 'en' or 'es'.
     * @return void
     * @throws \Exception On API error.
     */
    public function approve_credential($projectid, $credentialid, $templateid, $lang = 'es') {
        $base = $this->project_path($projectid) . '/credentials/' . rawurlencode((string) $credentialid);
        $query = '?keystore=keyvault&lang=' . rawurlencode((string) $lang);
        $body = ['templateId' => $templateid];

        try {
            $this->client->put($base . '/approve' . $query, $body);
            return;
        } catch (\Exception $e) {
            debugging('Blerify: /approve failed, retrying on /sign: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        $this->client->put($base . '/sign' . $query, $body);
    }

    /**
     * Step 3: Read the current state of a credential.
     *
     * @param string $projectid
     * @param string $credentialid
     * @param string $templateid Required: access is scoped to org + project + template.
     * @return array {
     *     status: string   PENDING while issuing, SENT once issued, DELIVERED once claimed.
     *     code: string|null Claim code, present once SENT. Used to build the wallet QR.
     *     pdf: string|null  Short-lived signed URL (~60s).
     *     thumbnail: string|null Short-lived signed URL (~60s).
     *     issued: string|null The signed verifiable credential, present once claimed.
     * }
     * @throws \Exception On API error.
     */
    public function poll_credential($projectid, $credentialid, $templateid, $organizationid = null) {
        $path = $this->project_path($projectid, $organizationid) .
                '/credentials/' . rawurlencode((string) $credentialid) .
                '/polling?templateId=' . rawurlencode((string) $templateid);

        $response = $this->client->get($path);
        if (!$response) {
            throw new \Exception('Failed to poll credential: empty response');
        }

        return [
            'status' => isset($response->status) ? $response->status : null,
            'code' => isset($response->code) ? $response->code : null,
            'pdf' => isset($response->pdf) ? $response->pdf : null,
            'thumbnail' => isset($response->thumbnail) ? $response->thumbnail : null,
            'issued' => isset($response->issued) ? $response->issued : null,
        ];
    }
}
