<?php
//namespace Docsmit;
/**
 * @license Docsmit API PHP SDK
 * (c) 2014-2015 Docsmit.com, Inc. http://www.docsmit.com
 * License: MIT
 */

class DocsmitAPI {

    private $email;
    private $password;
    private $softwareID;
    private $token;
    private $headers;
    private $curl_info;
    private $response_body;
    private $http_code;
    private $URIBase;

    const HTTP_OK = 200;
    const HTTP_UNAUTHORIZED = 401;

// SendType values
    const ST_CERTERR = "Certified, Electronic Return Receipt";
    const ST_CERTRR = "Certified, Return Receipt";
    const ST_CERT = "Certified";
    const ST_FIRST = "First Class";
    const ST_PRIORMWS = "Priority Mail with Signature";
    const ST_PRIORM = "Priority Mail";
    const ST_PRIORITY_FRE = "Priority Mail, Flat Rate Env";

// Envelope Values
    const ENV_10="#10";
    const ENV_FLAT="Flat";
    const ENV_PRIORITY_FLAT="Priority Flat";
    const ENV_PRIORITY_PADDED="Priority Padded";

// Binder Type (for scripts/screenplays type only)
    const BINDER_BRADS = "Brads13";
    const BINDER_CLIP = "Binder clip";

// single or double sided printing
    const SIDED_SINGLE = 1;
    const SIDED_DOUBLE = 2;

    public function __construct($email, $password, $softwareID, $URIBase="") {
        $this->URIBase = ($URIBase != "") ? $URIBase : "https://secure.docsmit.com/api/v1" ;
        $this->email = $email;
        $this->password = $password;
        $this->softwareID = $softwareID;
        $this->refreshToken();
    }

    public function setURIBase ($URIBase) { $this->URIBase = $URIBase; }

    private function URIBase () { return $this->URIBase; }

    public function responseBody() { return $this->response_body; }

    ## responseJSON() will deprecated soon.
    public function responseJSON() { return json_decode($this->responseBody()); }

    public function responseObject() { return json_decode($this->responseBody()); }

    function tokenWasOK() { return ($this->token != NULL); }

    function curlInfo() {
        return $this->curl_info;
    }
    public function status() { return $this->http_code; }

    function returnedError () { return ( $this->http_code >= 400); }

    function returnedSuccess () { return ( $this->http_code == 200 || $this->http_code == 201); }

    private function refreshToken() {
        $curl_post_data = array(
            'email' => $this->email,
            'password' => $this->password,
            'softwareID' => $this->softwareID
        );
        $json = json_encode($curl_post_data);
        $ch = curl_init($this->URIBase() . '/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        $this->response_body = curl_exec($ch);

        $ch_info = curl_getinfo($ch);
        $http_code = $ch_info["http_code"];
        curl_close($ch);
        if ($http_code == self::HTTP_OK && isset($this->responseJSON()->token))
            $this->token = $this->responseJSON()->token;
        else
            $this->token = NULL;
    }

    private function execAndFill($ch, $params = NULL, $contentType = "application/json", $jsonEncode = true) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:$contentType"));
        if ($params != NULL) {
            if ($jsonEncode)
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            else
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERPWD, $this->token);
        $this->response_body = curl_exec($ch);

        $this->curl_info = curl_getinfo($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        $this->headers = substr($this->response_body, 0, $header_size);
        $this->http_code = $this->curl_info["http_code"];
    }

    public function get($url, $params = NULL) {

        if ($params != NULL) {
            $qsSeparator = strrpos($url, "?") === FALSE ? '?' : '&';
            $url .= $qsSeparator . http_build_query($params);
        }

        $ch = curl_init($this->URIBase() . $url);
        $this->execAndFill($ch);
        if ($this->http_code == self::HTTP_UNAUTHORIZED) {
            $this->refreshToken();

            if ($params != NULL) {
                $qsSeparator = strrpos($url, "?") === FALSE ? '?' : '&';
                $url .= $qsSeparator . http_build_query($params);
            }

            $ch = curl_init($this->URIBase() . $url);
            $this->execAndFill($ch);
        }
    }

    public function post($url, $params = NULL, $isFile = false) {
        $ch = curl_init($this->URIBase() . $url);
        curl_setopt($ch, CURLOPT_POST, true);

        if ($isFile && !empty($params["filePath"]) && file_exists($params["filePath"])) {
            $finfo = new finfo(FILEINFO_MIME);
            $mimeType = $finfo->file($params["filePath"]);
            $params["file"] = new CurlFile($params["filePath"], $mimeType);
            $this->execAndFill($ch, $params, "multipart/form-data", false);
        } elseif ($isFile && !empty($params["blob"])) {
            $this->execAndFill($ch, $params, "multipart/form-data", false);
        }
        else
            $this->execAndFill($ch, $params);

        if ($this->http_code == self::HTTP_UNAUTHORIZED) {
            $this->refreshToken();
            $ch = curl_init($this->URIBase() . $url);
            curl_setopt($ch, CURLOPT_POST, true);
            if ($isFile && !empty($params["filePath"]) && file_exists($params["filePath"])) {
                $finfo = new finfo(FILEINFO_MIME);
                $mimeType = $finfo->file($params["filePath"]);
                $params["file"] = new CurlFile($params["filePath"], $mimeType);
                $this->execAndFill($ch, $params, "multipart/form-data", false);
            } elseif ($isFile && !empty($params["blob"])) {
                $this->execAndFill($ch, $params, "multipart/form-data", false);
            }
            else
                $this->execAndFill($ch, $params);
        }
    }

    public function postFile ($url, $params) {
        $ch = curl_init($this->URIBase() . $url);
        curl_setopt($ch, CURLOPT_POST, true);
        if (!empty($params["filePath"]) && file_exists($params["filePath"])) {
            $finfo = new finfo(FILEINFO_MIME);
            $mimeType = $finfo->file($params["filePath"]);
            $params["file"] = new CurlFile($params["filePath"], $mimeType);
            $this->execAndFill($ch, $params, "multipart/form-data", false);
        }
        else
            return false;

        if ($this->http_code == self::HTTP_UNAUTHORIZED) {
            $this->refreshToken();
            $ch = curl_init($this->URIBase() . $url);
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($params["filePath"]) && file_exists($params["filePath"])) {
                $finfo = new finfo(FILEINFO_MIME);
                $mimeType = $finfo->file($params["filePath"]);
                $params["file"] = new CurlFile($params["filePath"], $mimeType);
                $this->execAndFill($ch, $params, "multipart/form-data", false);
            }
            else
                return false;
        }
        return true;
    }

    public function postBlob($url,$params = NULL){
        $cv = new CurlVar();
        if (!empty($params["fields"])) {
            foreach ($params["fields"] as $name => $value) {
                $cv->addField($name, $value);
            }
        }
        $errors = array();
        if (!empty($params["files"])) {
            foreach ($params["files"] as $key => $file) {
                $fileID = ++$key;
                $errors_data = $cv->validateFile($file);
                if (!empty($errors_data)) {
                    $errors["errors"]["file" . $fileID]["message"] = "Error occured while uploading file.";
                    $errors["errors"]["file" . $fileID]["error_details"] = $errors_data;
                    continue;
                }
                $cv->addFile($file["filename"], $file["content"]);
            }
        }

        $cv->addOpt(CURLOPT_USERPWD, $this->token);
        $ch = curl_init($this->URIBase() . $url);
        $cv->execAndFill($ch);
        if ($cv->status() == CurlVar::HTTP_UNAUTHORIZED) {
            $cv = new CurlVar();
            if (!empty($params["fields"])) {
                foreach ($params["fields"] as $name => $value) {
                    $cv->addField($name, $value);
                }
            }
            if (!empty($params["files"])) {
                foreach ($params["files"] as $key => $file) {
                    $fileID = ++$key;
                    $errors_data = $cv->validateFile($file);
                    if (!empty($errors_data)) {
                        $errors["errors"]["file" . $fileID]["message"] = "Error occured while uploading file.";
                        $errors["errors"]["file" . $fileID]["error_details"] = $errors_data;
                        continue;
                    }
                    $cv->addFile($file["filename"], $file["content"]);
                }
            }
            $cv->addOpt(CURLOPT_USERPWD, $this->token);
            $ch = curl_init($this->URIBase() . $url);
            $cv->execAndFill($ch);
        }

        if (empty($errors))
            $this->response_body = $cv->responseBody();
        else {
            $response_body_arr = json_decode($cv->responseBody(), TRUE);
            $this->response_body = json_encode(array_merge($response_body_arr, $errors));
        }
        $this->http_code = $cv->status();
        $this->curl_info = $cv->curlInfo();

    }

    public function put($url, $params = NULL) {
        $ch = curl_init($this->URIBase() . $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        $this->execAndFill($ch, $params);
        if ($this->http_code == self::HTTP_UNAUTHORIZED) {
            $this->refreshToken();
            $ch = curl_init($this->URIBase() . $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            $this->execAndFill($ch, $params);
        }
    }

    public function delete($url, $params = NULL) {
        $ch = curl_init($this->URIBase() . $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        $this->execAndFill($ch, $params);
        if ($this->http_code == self::HTTP_UNAUTHORIZED) {
            $this->refreshToken();
            $ch = curl_init($this->URIBase() . $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            $this->execAndFill($ch, $params);
        }
    }
    static function hexHashPW($clearText = null){
        if(empty($clearText))
            return FALSE;
        return hash('sha512', $clearText);
    }

// If you don't have PECL with http_parse_headers
//see: http://php.net/manual/en/function.http-parse-headers.php#68698
}

/**
 * Description of CurlVar
 *
 * @author Docsmit
 */
/*
  Example usage:

  $cv = new CurlVar ($url);
  $cv->addField(...);
  $cv->addFile(...);
  $cv->addOpt(CURLOPT_USERPWD, $token)
  $cv->execAndFill();

 */

class CurlVar {

    private $opts = array();
    private $post_fields = array();
    private $file_fields = array();
    private $headers;
    private $curl_info;
    private $response_body;
    private $http_code;

    const HTTP_OK = 200;
    const HTTP_UNAUTHORIZED = 401;

    function __constructor() {
    }

    public function responseBody() {
        return $this->response_body;
    }

    public function responseJSON() {
        return json_decode($this->responseBody());
    }

    function curlInfo() {
        return $this->curl_info;
    }

    function status() {
        return $this->http_code;
    }

    function returnedError() {
        return ( $this->http_code >= 400);
    }

    function returnedSuccess() {
        return ( $this->http_code == 200 || $this->http_code == 201);
    }

    function addField($name, $value) {
        $this->post_fields[$name] = $value;
    }

    function addFile($name, $content) {
        $this->file_fields[] = array(
            "name" => $name,
            "content" => $content,
        );
    }

    function addOpt($opt, $val) {
        $this->opts[$opt] = $val;
    }

    function execAndFill($ch) {
        $delimiter = '-------------' . uniqid();
        $data = '';

        if (!empty($this->post_fields)) {
            foreach ($this->post_fields as $fieldName => $fieldValue) {
                $data .= "--" . $delimiter . "\r\n";
                $data .= 'Content-Disposition: form-data; name="' . $fieldName . '"';
                // note: double endline
                $data .= "\r\n\r\n";
                $data .= $fieldValue . "\r\n";
            }
        }
        if (!empty($this->file_fields)) {
            $count = 1;
            foreach ($this->file_fields as $key => $file) {
                $data .= "--" . $delimiter . "\r\n";
                $data .= 'Content-Disposition: form-data; name="file' . $count++ . '";' .
                        ' filename="' . $file['name'] . '"' . "\r\n";
                $data .= "\r\n";
                $data .= $file['content'] . "\r\n";
            }
        }

        $data .= "--" . $delimiter . "--\r\n";
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: multipart/form-data; boundary=' . $delimiter,
            'Content-Length: ' . strlen($data)));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if (!empty($this->opts)) {
            foreach ($this->opts as $opt => $value) {
                curl_setopt($ch, $opt, $value);
            }
        }
        $this->response_body = curl_exec($ch);
        $this->curl_info = curl_getinfo($ch);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        $this->headers = substr($this->response_body, 0, $header_size);
        $this->http_code = $this->curl_info["http_code"];
    }

    function validateFile($file = array()) {
        $errors = array();
        if (!empty($file)) {
            if (!isset($file["filename"]))
                $errors[] = "filename is required.";
            if (!isset($file["content"]))
                $errors[] = "content is required.";

            $allowedExts = array("jpg", "jpeg", "png", "doc", "docx", "xls", "xlsx", "pdf", "mov", "avi", "txt", "mp4", "zip");
            $allwedExtsStr = implode("|", $allowedExts);
            if (isset($file["filename"]) && !preg_match('/^[A-z0-9\(\)\s-]+\.(' . strtolower($allwedExtsStr) . ')$/', strtolower($file["filename"]))) {
                 $errors[] = $file["filename"] . " is invalid file.";
             }
            return $errors;
        }
    }
}
