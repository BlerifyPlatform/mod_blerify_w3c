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

namespace mod_blerify\client;

defined('MOODLE_INTERNAL') || die();

class client {

    /** @var \curl */
    private $curl;

    /** @var string */
    private $apihost;

    /** @var string */
    private $authapihost;

    /** @var string */
    private $clientid;

    /** @var string */
    private $organizationid;

    /** @var string */
    private $privatekey;

    /** @var string */
    private $iamaudience;

    /** @var string */
    private $tokenuri;

    /** @var string|null Cached access token */
    private $accesstoken;

    /** @var int Token expiration timestamp */
    private $tokenexpiry = 0;

    /** @var string|null Last error */
    public $error;

    public function __construct($curl = null) {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $sa = \mod_blerify\local\service_account::get_decoded();

        if (!empty($sa)) {
            if (!isset($sa['client_id']) || !isset($sa['private_key'])) {
                throw new \moodle_exception('error_not_configured', 'blerify');
            }

            $this->clientid = $sa['client_id'];
            $this->organizationid = $sa['organization_id'];
            $this->privatekey = $sa['private_key'];
            $this->iamaudience = $sa['iam_audience'];
            $this->tokenuri = $sa['token_uri'];

            $parsed = parse_url($sa['token_uri']);
            $baseurl = $parsed['scheme'] . '://' . $parsed['host'];
            if (!empty($parsed['port'])) {
                $baseurl .= ':' . $parsed['port'];
            }
            $this->apihost = $baseurl;
            $this->authapihost = $baseurl;

        } else {
            $this->clientid = get_config('mod_blerify', 'client_id');
            $this->organizationid = get_config('mod_blerify', 'organization_id');
            $this->privatekey = get_config('mod_blerify', 'private_key');
            $this->apihost = rtrim(get_config('mod_blerify', 'api_host'), '/');
            $this->authapihost = rtrim(get_config('mod_blerify', 'auth_api_host'), '/');
            $this->iamaudience = get_config('mod_blerify', 'iam_audience');
            $this->tokenuri = $this->authapihost . '/auth/v2/protocol/openid-connect/token';
        }

        if (empty($this->clientid) || empty($this->organizationid) || empty($this->privatekey)) {
            throw new \moodle_exception('error_not_configured', 'blerify');
        }

        foreach ([$this->tokenuri, $this->apihost] as $checkurl) {
            $parsedurl = parse_url((string)$checkurl);
            if (empty($parsedurl['scheme']) || $parsedurl['scheme'] !== 'https'
                    || empty($parsedurl['host']) || !self::host_allowed($parsedurl['host'])) {
                throw new \moodle_exception('error_not_configured', 'blerify');
            }
        }

        $this->curl = $curl ? $curl : new \curl();
        $this->curl->setopt([
            'CURLOPT_TIMEOUT' => 30,
            'CURLOPT_SSL_VERIFYPEER' => true,
            'CURLOPT_SSL_VERIFYHOST' => 2,
        ]);
        $this->error = null;
    }

    public function post($path, $data = []) {
        return $this->request('post', $path, $data);
    }

    public function put($path, $data = []) {
        return $this->request('put', $path, $data);
    }

    public function put_raw($path, $data = []) {
        return $this->request('put', $path, $data, true);
    }

    public function get($path) {
        return $this->request('get', $path);
    }

    public function get_organization_id() {
        return $this->organizationid;
    }

    public function get_api_host() {
        return $this->apihost;
    }

    private function request($method, $path, $data = null, $raw = false) {
        $accesstoken = $this->get_access_token();
        $url = $this->apihost . $path;

        $jsondata = ($data !== null) ? json_encode($data) : null;

        $this->curl->resetHeader();
        $this->curl->setHeader('Authorization: Bearer ' . $accesstoken);
        $this->curl->setHeader('Content-Type: application/json; charset=utf-8');

        $response = $this->curl->$method($url, $jsondata);

        $httpcode = $this->curl->get_info()['http_code'] ?? 0;

        if ($this->curl->error) {
            $this->error = $this->curl->error;
            throw new \Exception('Blerify API error: ' . $this->curl->error);
        }

        if ($httpcode >= 400) {
            $decoded = json_decode((string)$response, true);
            $msg = "HTTP $httpcode";
            if (is_array($decoded)) {
                if (!empty($decoded['message'])) {
                    $msg .= " message: {$decoded['message']}";
                }
                if (!empty($decoded['error'])) {
                    $msg .= " error: {$decoded['error']}";
                }
                if (isset($decoded['code'])) {
                    $msg .= " code: {$decoded['code']}";
                }
            } else if (is_string($response) && trim((string)$response) !== '') {
                $msg .= ' body: ' . trim((string)$response);
            }
            throw new api_exception("Blerify API error on " . strtoupper($method) . " $path: $msg", $httpcode);
        }

        if ($response === false || $response === null || $response === '') {
            return null;
        }

        if ($raw) {
            return $response;
        }

        return json_decode($response);
    }

    private function get_access_token() {
        if ($this->accesstoken && $this->tokenexpiry > (time() + 60)) {
            return $this->accesstoken;
        }

        $cache = \cache::make('mod_blerify', 'accesstokens');
        $cachekey = hash('sha256', $this->clientid . "\0" . $this->organizationid . "\0" . $this->tokenuri);
        $cached = $cache->get($cachekey);

        if (is_array($cached) && !empty($cached['token'])
                && !empty($cached['expiresat']) && (int)$cached['expiresat'] > (time() + 60)) {
            try {
                $encrypted = base64_decode($cached['token'], true);
                if ($encrypted !== false) {
                    $this->accesstoken = \core\encryption::decrypt($encrypted);
                    $this->tokenexpiry = (int)$cached['expiresat'];
                    return $this->accesstoken;
                }
            } catch (\Exception $e) {
                $cache->delete($cachekey);
            }
        }

        $jwt = $this->create_jwt();

        $postfields = [
            'client_id' => $this->clientid,
            'organization_id' => $this->organizationid,
            'client_assertion' => $jwt,
        ];
        $postdata = http_build_query($postfields, '', '&');

        $authcurl = new \curl();
        $authcurl->setopt([
            'CURLOPT_SSL_VERIFYPEER' => true,
            'CURLOPT_SSL_VERIFYHOST' => 2,
        ]);
        $authcurl->setHeader('Content-Type: application/x-www-form-urlencoded');
        $response = $authcurl->post($this->tokenuri, $postdata);

        $httpcode = $authcurl->get_info()['http_code'] ?? 0;

        if ($httpcode !== 200) {
            $detail = trim((string)$response);
            if ($detail !== '') {
                $decoded = json_decode($detail, true);
                if (is_array($decoded)) {
                    $detail = implode(' ', array_filter([
                        !empty($decoded['error']) ? "error: {$decoded['error']}" : '',
                        !empty($decoded['error_description']) ? "desc: {$decoded['error_description']}" : '',
                        !empty($decoded['message']) ? "message: {$decoded['message']}" : '',
                    ]));
                }
                $detail = ' ' . \core_text::substr($detail, 0, 300);
            }
            throw new \Exception("Failed to get access token. HTTP $httpcode." . $detail);
        }

        $tokendata = json_decode($response, true);
        if (!isset($tokendata['access_token'])) {
            throw new \Exception('Access token not found in auth response.');
        }

        $this->accesstoken = $tokendata['access_token'];
        $expiresin = isset($tokendata['expires_in']) ? (int)$tokendata['expires_in'] : 3600;
        $this->tokenexpiry = time() + $expiresin;

        if (!\core\encryption::key_exists()) {
            \core\encryption::create_key();
        }
        $cache->set($cachekey, [
            'token' => base64_encode(\core\encryption::encrypt($this->accesstoken)),
            'expiresat' => $this->tokenexpiry,
        ]);

        return $this->accesstoken;
    }

    private function create_jwt() {
        $now = time();

        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $payload = [
            'iss' => $this->clientid,
            'sub' => $this->clientid,
            'aud' => $this->iamaudience,
            'iat' => $now,
            'exp' => $now + 3600,
            'jti' => self::uuid4(),
        ];

        $headerb64 = self::base64url_encode(json_encode($header));
        $payloadb64 = self::base64url_encode(json_encode($payload));

        $signinginput = $headerb64 . '.' . $payloadb64;

        $signature = '';
        $success = openssl_sign($signinginput, $signature, $this->privatekey, OPENSSL_ALGO_SHA256);
        if (!$success) {
            throw new \Exception('Failed to sign JWT: ' . openssl_error_string());
        }

        $signatureb64 = self::base64url_encode($signature);

        return $signinginput . '.' . $signatureb64;
    }

    private static function uuid4() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private static function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Whether a host is within the allowed Blerify domain.
     *
     * @param string $host
     * @return bool
     */
    private static function host_allowed($host) {
        $host = strtolower($host);
        return $host === 'blerify.com' || substr($host, -12) === '.blerify.com';
    }
}
