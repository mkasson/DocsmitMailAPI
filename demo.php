<HTML>
    <body>
        <?php
        require_once './DocsmitAPI.php';


/////////////////
//////// Please fill in the following 4 items:

        define("PATHTOFILE", "http://localhost/docsmitTest/demo.php"); // Replace with the URL of this demo file on your server.  e.g. http://test.coolguys.com/Docsmit/demo.php
        $email = 'XXX'; // Your registered email with Docsmit
        $password = 'YYY'; // Your Docsmit password
        $softwareID = 'ZZZ'; Your 32 character softwareID

/////////////////


        define("SECUREURL","https://secure.tracksmit.com"); // Docsmit API URI
        define("TESTPDF", "./testPDF.pdf");

        $testCase = isset($_REQUEST["testCase"]) && !empty($_REQUEST["testCase"]) ? $_REQUEST["testCase"] : null;
        echo "<h1><a href='" . PATHTOFILE . "'>" . "Docsmit API Test Cases</a></h1><h3>Results for: $testCase</h3>";
        if (!empty($_GET["error"])) {
            $error = $_GET["error"];
            switch ($error) {
                case "invalidMsgID":
                    echo "Please pass valid messageID with URI. messageID is required for this API.";
                    exit;
                    break;

                default:
                    break;
            }
        }

        function mylink($name) {
            $ref = PATHTOFILE . '?testCase=' . $name;
            if (isset($_GET["messageID"]))
                $ref .="&messageID=" . $_GET["messageID"];
            return "<a href='$ref'>$name</a><BR>";
        }

        function get_mime_content_type($fileBlob1) {
            $signatures = array(
                'jpg' => "\xFF\xD8\xFF",
                'jpeg' => "\xFF\xD8\xFF",
                'png' => "\x89PNG",
                'doc' => "\x0d\x44\x4f\x43",
                'docx' => "\x50\x4B\x03\x04",
                'xls' => "\xd0\xcf\x11\xe0",
                'xlsx' => "\x50\x4B\x03\x04",
                'pdf' => "\x25\x50\x44\x46",
                'mp4' => "\x00\x00\x00\xnn",
                'zip' => "\x1F\x9D"
            );

            echo "Blob : :" . $fileBlob1;
            echo "<br>first4Bytes :" . $first4Bytes = substr($fileBlob1, 0, 4);
            $ext = "";
            foreach ($signatures as $imageType => $signature) {
                if (strpos($first4Bytes, $signature) === 0) {
                    $ext = $imageType;
                }
            }

            $mime_types = array(
                'txt' => 'text/plain',
                'htm' => 'text/html',
                'html' => 'text/html',
                'php' => 'text/html',
                'css' => 'text/css',
                'js' => 'application/javascript',
                'json' => 'application/json',
                'xml' => 'application/xml',
                'swf' => 'application/x-shockwave-flash',
                'flv' => 'video/x-flv',
                // images
                'png' => 'image/png',
                'jpe' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'jpg' => 'image/jpeg',
                'gif' => 'image/gif',
                'bmp' => 'image/bmp',
                'ico' => 'image/vnd.microsoft.icon',
                'tiff' => 'image/tiff',
                'tif' => 'image/tiff',
                'svg' => 'image/svg+xml',
                'svgz' => 'image/svg+xml',
                // archives
                'zip' => 'application/zip',
                'rar' => 'application/x-rar-compressed',
                'exe' => 'application/x-msdownload',
                'msi' => 'application/x-msdownload',
                'cab' => 'application/vnd.ms-cab-compressed',
                // audio/video
                'mp3' => 'audio/mpeg',
                'qt' => 'video/quicktime',
                'mov' => 'video/quicktime',
                // adobe
                'pdf' => 'application/pdf',
                'psd' => 'image/vnd.adobe.photoshop',
                'ai' => 'application/postscript',
                'eps' => 'application/postscript',
                'ps' => 'application/postscript',
                // ms office
                'doc' => 'application/msword',
                'docx' => 'application/msword',
                'rtf' => 'application/rtf',
                'xls' => 'application/vnd.ms-excel',
                'ppt' => 'application/vnd.ms-powerpoint',
                // open office
                'odt' => 'application/vnd.oasis.opendocument.text',
                'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            );

            if (array_key_exists($ext, $mime_types)) {
                return $mime_types[$ext];
            }
        }

        function isValidMessageID($messageID = null) {
            $messageID = filter_var($messageID, FILTER_VALIDATE_INT);
            if (false === $messageID) {
                header('Location: ' . $_SERVER['REQUEST_URI'] . "&error=invalidMsgID");
                exit;
            }
            return $messageID;
        }

        $messageID = isset($_GET["messageID"]) ? validateMsgID($_GET["messageID"]) : 0;
        $hashedPW = DocsmitAPI::hexHashPW($password);
        $docsmit = new DocsmitAPI($email, $hashedPW, $softwareID, SECUREURL . "/api/v1");
        $cases = [];
        $cases[] = "full_app_test";
        $cases[] = "sent_folder";

        switch ($testCase) {
            case "full_app_test":
                ob_start();

                ////// SECTION 1
                echo "<BR><pre>***** Full App Test <BR>";
                echo "<pre>*** Section 1 - Get account information/balance ".  date("m-d-Y H:i:s") . "<BR>";
                $docsmit = new DocsmitAPI($email, $hashedPW, $softwareID, SECUREURL . "/api/v1");
                $docsmit->get("/account/info");
                echo "<pre>OUTPUT /account/info = " . print_r($docsmit->responseJSON(), true) . "</pre><BR>";
                $acctBalMin = 20;
                if ($docsmit->responseJSON()->creditBalance < $acctBalMin) {
                    echo "Account Balance of " . $docsmit->responseJSON()->creditBalance. " is less than required amount of $acctBalMin.<BR>Not enough to continue.<BR>";
                    break;
                }
                ob_flush();

                ////// SECTION 2
                $docsmit = new DocsmitAPI($email, $hashedPW, $softwareID, SECUREURL . "/api/v1");
                echo "<BR><pre>*** Section 2 - Login ".  date("m-d-Y H:i:s") . "<BR>";
                echo "<pre>OUTPUT new DocsmitAPI = " . print_r($docsmit->responseJSON(), true) . "</pre><BR>";
                ob_flush();

                echo "<BR><pre>*** Section 3 - Create new message with /messages/new " . date("m-d-Y H:i:s") . "<BR>";
                $body = array(
                    "title" => "Test message from 'full_app_test' - " . date("m-d-Y H:i:s"),
                    "physicalParties" => array(
                        array(
                            "firstName" => "Mike",
                            "lastName" => "Knight",
                            "organization" => "Docsmit.com, Inc.",
                            "address1" => "74 S Livingston Ave",
                            "city" => "Livingston",
                            "state" => "NJ",
                            "postalCode" => "07039",
                            "sendType" => DocsmitAPI::ST_CERTERR,
                            "envelope" => DocsmitAPI::ENV_10,
                            "mailClass"=>"certified"
                        ),
                        array(
                            "firstName" => "Kevin",
                            "lastName" => "Jensen",
                            "organization" => "SIG Management, LLC",
                            "address1" => "808 Bergen Ave, Suite 101",
                            "city" => "Jersey City",
                            "state" => "NJ",
                            "postalCode" => "07305",
                            "sendType" => DocsmitAPI::ST_CERTERR,
                            "envelope" => DocsmitAPI::ENV_10,
                            "mailClass"=>"certified"
                        )
                    ),
                    "rtnName"=> "John Paul",
                    "rtnOrganization"=> "ABC & Co",
                    "rtnAddress1"=> "26 Journal Square, 8th Floor",
                    "rtnCity"=> "Jersey City",
                    "rtnState"=> "NJ",
                    "rtnZip"=> "07306",
                    "billTo" => "Testing",
                );
                $docsmit->post('/messages/new', $body);
                echo "<pre>INPUT body for /messages/new = " . print_r($body, true) . "</pre><BR>";
                echo "<pre> " . print_r($docsmit->responseJSON(),true) . "</pre><BR>";
                if(isset($docsmit->responseJSON()->code) && $docsmit->responseJSON()->code != 201)
                {
                        die("Error occurred while creating new message.");
                }
                $newMessageID =  $docsmit->responseJSON()->messageID;
                echo "<BR>OUTPUT New message's messageID = " . $newMessageID . "<BR>";
                ob_flush();


                echo "<BR><pre>*** Section 4 - Create upload file to the new message with /messages/{msgID}/upload " . date("m-d-Y H:i:s") . "<BR>";
                $docsmit = new DocsmitAPI($email, $hashedPW, $softwareID, SECUREURL . "/api/v1");
                $params['filePath'] = TESTPDF;
                echo "<pre>INPUT params for /upload = " . print_r($params, true) . "</pre><BR>";
                $docsmit->post("/messages/$newMessageID/upload", $params, 1);
                echo "OUTPUT /upload status = ". $docsmit->status() . "<BR>";
                echo "<pre>OUTPUT /upload response = " . print_r($docsmit->responseJSON(), true) . "</pre><BR>";
                ob_flush();

                echo "<BR><pre>*** Section 5 - get price to send " . date("m-d-Y H:i:s") . "<BR>";
                $docsmit = new DocsmitAPI($email, $hashedPW, $softwareID, SECUREURL . "/api/v1");
                $params = array();
                echo "<pre>INPUT params for /priceCheck = " . print_r($params, true) . "</pre><BR>";
                $docsmit->get("/messages/$newMessageID/priceCheck", $params);
                echo "OUTPUT /send status = ". $docsmit->status() . "<BR>";
                echo "<pre>/priceCheck response = " . print_r($docsmit->responseJSON(), true) . "</pre><BR>";
                ob_flush();

                echo "<BR><pre>*** Section 6 - Send the message " . date("m-d-Y H:i:s") . "<BR>";
                $docsmit = new DocsmitAPI($email, $hashedPW, $softwareID, SECUREURL . "/api/v1");
                $body = array();
                $docsmit->post("/messages/$newMessageID/send", $body);
                echo "OUTPUT /send status = ". $docsmit->status() . "<BR>";
                echo "<pre>/send response = " . print_r($docsmit->responseJSON(), true) . "</pre><BR>";
                ob_flush();

                echo "<BR><pre>*** Section 7 - Get the message's status " . date("m-d-Y H:i:s") . "<BR>";
                $docsmit = new DocsmitAPI($email, $hashedPW, $softwareID, SECUREURL . "/api/v1");
                $arr["messageIDList"] = $newMessageID;
                $docsmit->get('/messages/sent', $arr);
                echo "OUTPUT /sent status = ". $docsmit->status() . "<BR>";
                echo "<pre>/send response = " . print_r($docsmit->responseJSON(), true) . "</pre><BR>";

                $testCase = null; // so we don't see a dump of $docsmit again
                echo "<BR><pre>***** COMPLETION " . date("m-d-Y H:i:s") . "<BR>";
                ob_end_flush();

                break;
            case "sent_folder":
                $docsmit = new DocsmitAPI($email, $hashedPW, $softwareID, SECUREURL . "/api/v1");
                $arr = array();
                //$arr["debug"] = true;
                $docsmit->get('/messages/sent', $arr);
                break;

            case null:
                echo "Click a test case below for a result.<BR>";
                break;
            default:
                echo "Test Case Not Found. Click one of the test cases.";
                break;
        }
        if ($testCase != null) {
            echo "HTTP response code = " . $docsmit->status() . "<BR>";
            echo "responseBody = <pre>" . $docsmit->responseBody() . "</pre><BR>";
        }
        echo "<h3>Available Test Cases:</h3>";
        foreach ($cases as $index => $case) {
            echo ++$index . ". " . mylink($case);
        }
        ?>
    </body>
</HTML>
