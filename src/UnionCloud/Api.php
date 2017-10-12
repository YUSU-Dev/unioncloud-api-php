<?php

namespace UnionCloud;

use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
use \Exception as Exception;

use Illuminate\Support\Collection;

/**
 * UnionCloud API wrapper
 *
 * @author Liam McDaid <liam@liammcdaid.com>
 */
class Api {

    private $VERSION = "0.1.1";
    
    private $client;
    private $host;
    private $options;
    
    private $auth_token;
    private $auth_token_expires;
    
    

    public function __construct(array $options = []) {
        $this->client = new Client();
        
        // verify ssl certs incase server misconfigured
        $guzzleClient = new GuzzleClient(array(
            "verify" => dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . "unioncloud.pem"
        ));
        $this->client->setClient($guzzleClient);
        
        $this->client->setServerParameters([
            "HTTP_USER_AGENT" => "UnionCloud API Wrapper (PHP) v" . $this->VERSION,
            "HTTP_Content-Type" => "application/json",
            "HTTP_accept-version" => "v1",
            "HTTPS" => true
        ]);
        
        $this->options = $options;
    }
    
    

    private function _api_request($method, $endpoint, array $parameters = [], array $files = [], array $server = [], array $content = [], $changeHistory = true) {
        $uri = "https://" . $this->host . "/api" . $endpoint . "?" . http_build_query($parameters);
       
        // include auth token, unless authenticating
        if ($endpoint != "/authenticate") {
            if ($this->auth_token_expires < time()) {
                throw new Exception("Auth Token has expired", 401);
            }
            $this->client->setServerParameter("HTTP_auth_token", $this->auth_token);
        }
        
        // encode the post data
        if (empty($content)) {
            $post_body = "";
        } else {
            $post_body = json_encode($content, JSON_UNESCAPED_SLASHES);
        }

        $this->client->request($method, $uri, $parameters, $files, $server, $post_body, $changeHistory);
        $response_body = $this->client->getResponse()->getContent();
        
        if (array_key_exists("include_debug_info", $this->options) && $this->options["include_debug_info"]) {
            $response = array_merge(["request" => $this->_request_parse()], json_decode($response_body, true));
        } else {
            $response = json_decode($response_body, true);
        }
        
        // throw exceptions when an error is returned
        if (array_key_exists("errors", $response)) {
            throw new Exception($response["errors"]["error_message"], $response["errors"]["error_code"]);
        } else if (array_key_exists("error", $response)) {
            throw new Exception($response["error"]["message"], $response["error"]["code"]);
        }
        
        return $response;
    }
    
    //
    // Debugging: Get access to http request / reponse
    //
    public function _request_headers() {
        return $this->client->getRequest()->getServer();
    }

    public function _request_paramters() {
        return $this->client->getRequest()->getParameters();
    }

    public function _request_body() {
        return $this->client->getRequest()->getContent();
    }

    public function _response_headers() {
        $headers = new Collection($this->client->getResponse()->getHeaders());
        return $headers->map(function ($item, $key) {
                    return $item[0];
                })->toArray();
    }

    public function _response_body() {
        return $this->client->getResponse()->getContent();
    }

    
    
    #
    # Parse Request details
    #
    public function _request_parse() {
        $headers = $this->_response_headers();
        $paramters = $this->_request_paramters();

        $r = [];
        //$r["parameters"] = $this->_request_paramters();
        //$r["headers"] = $this->_response_headers();
        $r["id"] = $headers["X-Request-Id"];
        $r["uri"] = $this->client->getRequest()->getUri();
        $r["parameters"] = $this->client->getRequest()->getParameters();
        $r["body"] = $this->_request_body();
        $r["token"] = $this->auth_token;
        $r["token_expires"] = $this->auth_token_expires;
        $r["status"] = $headers["Status"];
        $r["runtime"] = doubleval($headers["X-Runtime"]);

        // rate-limits
        if (array_key_exists("X-RateLimit-Remaining", $headers)) {
            $r["ratelimit"] = [
                "remaining" => intval($headers["X-RateLimit-Remaining"]),
                "limit" => intval($headers["X-RateLimit-Limit"]),
                "reset" => intval($headers["X-RateLimit-Reset"]),
            ];
        }

        // pagenation
        if (array_key_exists("total_pages", $headers)) {
            $r["pages"] = [
                "current" => array_key_exists("page", $paramters) ? intval($paramters["page"]) : 1,
                "total" => intval($headers["total_pages"]),
            ];
            $r["records"] = [
                "per_page" => intval($headers["records_per_page"]),
                "total" => intval($headers["total_records"]),
            ];
        }

        return $r;
    }
    
    

    public function setHost($domain) {
        $this->host = $domain;
    }

    public function getHost() {
        return $this->host;
    }
    
    

    public function setOptions($options) {
        $this->options = array_merge($this->options, $options);
    }

    public function getOptions() {
        return $this->options;
    }
    
    

    public function setAuthToken($token, $token_expires) {
        $this->auth_token = $token;
        $this->auth_token_expires = $token_expires;
        return $token_expires;
    }

    public function getAuthToken() {
        return [
            "token" => $this->auth_token,
            "expires" => $this->auth_token_expires,
            "time_left" => $this->auth_token_expires - time()
        ];
    }
    
    

    #
    # Authenticate
    #
    public function authenticate($user_email, $user_password, $app_id, $app_password) {
        $data = [
            "email" => $user_email,
            "password" => $user_password,
            "app_id" => $app_id,
            "date_stamp" => strval(time()),
            "hash" => hash("sha256", $user_email . $user_password . $app_id . strval(time()) . $app_password),
        ];

        $response = $this->_api_request("POST", "/authenticate", [], [], [], $data);

        if ($response["result"] == "SUCCESS") {
            $expires_at = time() + $response["response"]["expires"];
            $this->setAuthToken($response["response"]["auth_token"], $expires_at);
            return $expires_at;
        }
    }

    
    
    #
    # Uploads
    #
    public function upload_student($data) {
        return $this->_api_request("POST", "/json/upload/students", [], [], [], ["data" => $data]);
    }

    public function upload_guest($data) {
        return $this->_api_request("POST", "/json/upload/guests", [], [], [], ["data" => $data]);
    }

    public function upload_programme($data) {
        return $this->_api_request("POST", "/json/upload/programmes", [], [], [], ["data" => $data]);
    }
    
    

    #
    # Users
    #
    public function users($mode = "standard", $page = 1) {
        return $this->_api_request("GET", "/users", ["mode" => $mode, "page" => $page]);
    }

    public function user_search($filters, $mode = "standard", $page = 1) {
        return $this->_api_request("POST", "/users/search", ["mode" => $mode, "page" => $page], [], [], ["data" => $filters], null);
    }

    public function user_get($uid, $mode = "standard") {
        return $this->_api_request("GET", "/users/" . $uid, ["mode" => $mode]);
    }

    public function user_get_group_memberships($uid, $mode = "standard", $page = 1) {
        return $this->_api_request("GET", "/users/" . $uid . "/user_group_memberships", ["mode" => $mode, "page" => $page]);
    }

    public function user_update($uid, $data) {
        return $this->_api_request("PUT", "/users/" . $uid, [], [], [], ["data" => $data]);
    }

    public function user_delete($uid) {
        return $this->_api_request("DELETE", "/users/" . $uid);
    }

    
    
    #
    # UserGroups
    #
    public function usergroups($mode = "standard", $page = 1) {
        return $this->_api_request("GET", "/user_groups", ["mode" => $mode, "page" => $page]);
    }

    public function usergroup_search($filters, $mode = "standard", $page = 1) {
        return $this->_api_request("GET", "/user_groups/search", ["mode" => $mode, "page" => $page], [], [], ["data" => $filters]);
    }

    public function usergroup_create($name, $description, $folder_id = null) {
        return $this->_api_request("POST", "/user_groups", [], [], [], ["data" => [
                        "ug_name" => $name,
                        "ug_description" => $description,
                        "folder_id" => $folder_id,
        ]]);
    }

    public function usergroup_get($ug_id, $mode = "standard") {
        return $this->_api_request("GET", "/user_groups/" . $ug_id, ["mode" => $mode]);
    }

    public function usergroup_get_members($ug_id, $mode = "standard", $page = 1, $from = null, $to = null) {
        return $this->_api_request("GET", "/user_groups/" . $ug_id . "/user_group_memberships", ["mode" => $mode, "page" => $page]);
    }

    public function usergroup_update($ug_id, $data) {
        return $this->_api_request("PUT", "/user_groups/" . $ug_id, [], [], [], ["data" => $data]);
    }

    public function usergroup_delete($ug_id) {
        return $this->_api_request("DELETE", "/user_groups/" . $ug_id);
    }

    public function usergroup_folderstructure() {
        return $this->_api_request("GET", "/user_groups/folderstructure");
    }

    
    
    #
    # UserGroup Memberships
    #
    public function usergroup_membership_create($uid, $ug_id, $expire_date) {
        return $this->_api_request("POST", "/user_group_memberships", [], [], [], ["data" => [
                        "uid" => $uid, "ug_id" => $ug_id, "expire_date" => $expire_date
        ]]);
    }

    public function usergroup_membership_create_multiple(array $data) {
        return $this->_api_request("POST", "/user_group_memberships/upload", [], [], [], ["data" => $data]);
    }

    public function usergroup_membership_update($ugm_id, $expire_date) {
        return $this->_api_request("PUT", "/user_group_memberships/" . $ugm_id, [], [], [], ["data" => [
                        "expire_date" => $expire_date
        ]]);
    }

    public function usergroup_membership_delete($ugm_id) {
        return $this->_api_request("DELETE", "/user_group_memberships/" . $ugm_id);
    }

    public function usergroup_membership_delete_multiple(array $data) {
        return $this->_api_request("POST", "/user_group_memberships/delete", [], [], [], ["data" => $data]);
    }

    
    
    #
    # Event Types
    #
    public function eventtypes_get() {
        return $this->_api_request("GET", "/event_types");
    }

    
    
    #
    # Events
    #
    public function events($mode = "standard") {
        return $this->_api_request("GET", "/events", ["mode" => $mode]);
    }

    public function event_search($filters, $mode = "standard") {
        return $this->_api_request("POST", "/events/search", ["mode" => $mode], [], [], ["data" => $filters]);
    }

    public function event_create($data) {
        return $this->_api_request("POST", "/events", [], [], [], ["data" => $data]);
    }

    public function event_get($event_id, $mode = "standard") {
        return $this->_api_request("GET", "/events/" . $event_id, ["mode" => $mode]);
    }

    public function event_update($event_id, $data) {
        return $this->_api_request("PUT", "/events/" . $event_id, [], [], [], ["data" => $data]);
    }

    public function event_cancel($event_id) {
        return $this->_api_request("PUT", "/events/" . $event_id . "/cancel");
    }

    public function event_attendees($event_id, $mode = "standard") {
        return $this->_api_request("GET", "/events/" . $event_id . "/attendees", ["mode" => $mode]);
    }

    
    
    #
    # Event Ticket Types
    #
    public function event_tickettype_create($event_id, $data) {
        return $this->_api_request("POST", "/events/" . $event_id . "/event_ticket_types", [], [], [], ["data" => $data]);
    }

    public function event_tickettype_update($event_id, $event_ticket_type_id, $data) {
        return $this->_api_request("PUT", "/events/" . $event_id . "/event_ticket_types/" . $event_ticket_type_id, [], [], [], ["data" => $data]);
    }

    public function event_tickettype_delete($event_id, $event_ticket_type_id) {
        return $this->_api_request("DELETE", "/events/" . $event_id . "/event_ticket_types/" . $event_ticket_type_id);
    }

    
    
    #
    # Event Questions
    #
    public function event_question_create($event_id, $data) {
        return $this->_api_request("POST", "/events/" . $event_id . "/questions", [], [], [], ["data" => $data]);
    }

    public function event_question_update($event_id, $question_id, $data) {
        return $this->_api_request("PUT", "/events/" . $event_id . "/questions/" . $question_id, [], [], [], ["data" => $data]);
    }

    public function event_question_delete($event_id, $question_id) {
        return $this->_api_request("DELETE", "/events/" . $event_id . "/questions/" . $question_id);
    }

    
    
    #
    # eVoting Elections
    #
    public function election_categories($page = 1) {
        return $this->_api_request("GET", "/election_categories", ["page" => $page]);
    }

    public function election_category_get($category_id) {
        return $this->_api_request("GET", "/election_categories/" . $category_id);
    }

    public function election_positions($page = 1, $mode = "full") {
        return $this->_api_request("GET", "/election_positions", ["page" => $page, "mode" => $mode]);
    }

    public function election_position_get($position_id, $mode = "standard") {
        return $this->_api_request("GET", "/election_positions/" . $position_id, ["mode" => $mode]);
    }

    public function elections($page = 1, $mode = "full") {
        return $this->_api_request("GET", "/elections", ["mode" => $mode, "page" => $page]);
    }

    public function election_get($election_id, $mode = "full") {
        return $this->_api_request("GET", "/elections/" . $election_id, ["mode" => $mode]);
    }

    public function election_standings($election_id, $page = 1, $mode = "full") {
        return $this->_api_request("GET", "/elections/" . $election_id . "/election_standings", ["page" => $page, "mode" => $mode]);
    }

    public function election_voters($election_id, $voter_type = "actual", $page = 1) {
        $r = $this->_api_request("GET", "/elections/" . $election_id . "/election_voters", ["page" => $page, "voter_type" => $voter_type]);

        if (array_key_exists("file_path", $r)) {
            $file = $r["file_path"];
            $src = file_get_contents($file);
            return json_decode($src, true);
        } else {
            return $r;
        }
    }

    public function election_voters_demographics($election_id, $voter_type = "actual", $page = 1, $mode = "full") {
        $r = $this->_api_request("GET", "/elections/" . $election_id . "/election_voters_demographics", ["page" => $page, "voter_type" => $voter_type, "mode" => $mode]);

        if (array_key_exists("file_path", $r)) {
            $file = $r["file_path"];
            $src = file_get_contents($file);
            return json_decode($src, true);
        } else {
            return $r;
        }
    }

    public function election_votes($election_id, $page = 1) {
        return $this->_api_request("GET", "/elections/" . $election_id . "/votes", ["page" => $page]);
    }

    
    
    #
    # Groups (Student Groups)
    #
    public function groups($mode = "full", $page = 1) {
        return $this->_api_request("GET", "/groups", ["mode" => $mode, "page" => $page]);
    }

    public function group_get($group_id, $mode = "full") {
        return $this->_api_request("GET", "/groups/" . $group_id, ["mode" => $mode]);
    }

    public function group_join($group_id, $uid, $membership_type_id) {
        return $this->_api_request("POST", "/groups/" . $group_id . "/join", [], [], [], ["data" => [
            "uid" => $uid, "membership_type_id" => $membership_type_id
        ]]);
    }

}
