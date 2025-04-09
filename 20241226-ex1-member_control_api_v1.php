<?php

const DB_SERVER   = "localhost";
const DB_USERNAME = "owner01";
const DB_PASSWORD = "123456";
const DB_NAME     = "testdb";

// 建立連線
function create_connection()
{
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if (! $conn) {
        echo json_encode(["state" => false, "message" => "連線失敗"]);
        exit;
    }
    return $conn;
}

// 取得json的資料
function get_json_input()
{
    $data = file_get_contents("php://input");
    return json_decode($data, true);
}

// 回復JSON的訊息
function respond($state, $message, $data = null)
{
    echo json_encode(["state" => $state, "message" => $message, "data" => $data]);
}

// 上傳圖片
function get_photo()
{
    if (isset($_FILES['file_upload']) && $_FILES['file_upload']['name'] != "") {

        $fileTmpPath = $_FILES['file_upload']['tmp_name'];
        $fileName    = $_FILES['file_upload']['name'];
        $fileSize    = $_FILES['file_upload']['size'];
        $fileType    = $_FILES['file_upload']['type'];

        $uploadDir = 'uploads/user/';
        $newFileName = date("YmdHis") . '_' . basename($fileName);
        $uploadPath  = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $uploadPath)) {
            return $uploadPath;
        }
    }
}

// 更新圖片
function get_update_photo()
{
    $p_oldphotopath = $_POST["updateModal_oldphotopath"];
    if (isset($_FILES['updateModal_uploadPath']) && $_FILES['updateModal_uploadPath']['name'] != "") {
        unlink($p_oldphotopath);

        $fileTmpPath = $_FILES['updateModal_uploadPath']['tmp_name'];
        $fileName    = $_FILES['updateModal_uploadPath']['name'];
        $fileSize    = $_FILES['updateModal_uploadPath']['size'];
        $fileType    = $_FILES['updateModal_uploadPath']['type'];


        $uploadDir = 'uploads/user/';

        $newFileName = date("YmdHis") . '_' . basename($fileName);
        $uploadPath  = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $uploadPath)) {
            return $uploadPath;
        }
    }
}

// 會員註冊
function register_user()
{

    if ($uploadPath = get_photo()) {

        if (isset($_POST["username_reg"], $_POST["password_reg"], $_POST["tel_reg"], $_POST["email_reg"])) {
            $p_username = $_POST["username_reg"];
            $p_password = password_hash(trim($_POST["password_reg"]), PASSWORD_DEFAULT);
            $p_tel      = trim($_POST["tel_reg"]);
            $p_email    = trim($_POST["email_reg"]);
            if ($p_username && $p_password && $p_tel && $p_email && $uploadPath) {
                $conn = create_connection();

                $stmt = $conn->prepare("INSERT INTO member_test(userName, pwd, tel, email, uploadPath) VALUES(?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $p_username, $p_password, $p_tel, $p_email, $uploadPath);
                if ($stmt->execute()) {
                    respond(true, '註冊成功!');
                } else {
                    respond(false, '註冊失敗!');
                }
                $stmt->close();
                $conn->close();
            } else {
                respond(false, "欄位不得為空!");
            }
        } else {
            respond(false, "欄位錯誤!");
        }
    } else {
        respond(false, "檔案上傳失敗!");
    }
}

// 會員登入
function login_user()
{
    $input = get_json_input();

    if (isset($input["username"], $input["password"])) {
        $p_username = trim($input["username"]);
        $p_password = trim($input["password"]);
        if ($p_username && $p_password) {
            $conn = create_connection();

            $stmt = $conn->prepare("SELECT * FROM member_test WHERE userName = ?");
            $stmt->bind_param("s", $p_username); 
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                if (password_verify($p_password, $row["pwd"])) {
                    $uid01 = substr(hash('sha256', time()), 10, 4) . substr(bin2hex(random_bytes(16)), 4, 4);
                    $update_stmt = $conn->prepare("UPDATE member_test SET Uid01 = ? WHERE userName = ?");
                    $update_stmt->bind_param('ss', $uid01, $p_username);
                    if ($update_stmt->execute()) {

                        $user_stmt = $conn->prepare("SELECT userName, tel, email, Uid01, created_at FROM member_test WHERE userName = ?");
                        $user_stmt->bind_param("s", $p_username);
                        $user_stmt->execute();
                        $user_data = $user_stmt->get_result()->fetch_assoc();
                        respond(true, "登入成功!", $user_data);
                    } else {
                        respond(false, "登入失敗, UID更新失敗!");
                    }
                } else {
                    respond(false, "登入失敗, 密碼錯誤!");
                }
            } else {
                respond(false, "登入失敗, 該帳號不存在!");
            }
            $stmt->close();
            $conn->close();
        } else {
            respond(false, "登入失敗, 欄位不得為空!");
        }
    } else {
        respond(false, "登入失敗, 欄位錯誤!");
    }
}

// 驗證會員_uid
function check_uid()
{
    $input = get_json_input();
    if (isset($input["uid01"])) {
        $p_uid = trim($input["uid01"]);
        if ($p_uid) {
            $conn = create_connection();

            $stmt = $conn->prepare("SELECT id, userName, addr, tel, email, Uid01, level, created_at FROM member_test WHERE Uid01 = ?");
            $stmt->bind_param("s", $p_uid);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $userdata = $result->fetch_assoc();
                respond(true, "驗證成功!", $userdata);
            } else {
                respond(false, "驗證失敗!");
            }
            $stmt->close();
            $conn->close();
        } else {
            respond(false, "欄位不得為空!");
        }
    } else {
        respond(false, "欄位錯誤!");
    }
}

function check_username()
{
    $input = get_json_input();
    if (isset($input["username"])) {
        $p_username = trim($input["username"]);
        if ($p_username) {
            $conn = create_connection();

            $stmt = $conn->prepare("SELECT userName FROM member_test WHERE userName = ?");
            $stmt->bind_param("s", $p_username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {

                respond(false, "帳號已存在，不可以使用!");
            } else {
                respond(true, "帳號不存在，可以使用!");
            }
            $stmt->close();
            $conn->close();
        } else {
            respond(false, "欄位不得為空!");
        }
    } else {
        respond(false, "欄位錯誤!");
    }
}

// 驗證會員註冊電話
function check_tel()
{
    $input = get_json_input();
    if (isset($input["tel_reg"])) {
        $p_tel = trim($input["tel_reg"]);
        if ($p_tel) {
            $conn = create_connection();

            $stmt = $conn->prepare("SELECT tel FROM member_test WHERE tel = ?");
            $stmt->bind_param("s", $p_tel);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                respond(false, "電話重複，不可以使用!");
            } else {
                respond(true, "電話未重複，可以使用!");
            }
            $stmt->close();
            $conn->close();
        } else {
            respond(false, "欄位不得為空!");
        }
    } else {
        respond(false, "欄位錯誤!");
    }
}

// 會員電話更新
function check_update_tel()
{
    $input = get_json_input();
    if (isset($input["updateModal_tel"])) {
        $p_tel = trim($input["updateModal_tel"]);
        if ($p_tel) {
            $conn = create_connection();

            $stmt = $conn->prepare("SELECT tel FROM member_test WHERE tel = ?");
            $stmt->bind_param("s", $p_tel);
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                respond(false, "電話重複，不可以使用!");
            } else {
                respond(true, "電話未重複，可以使用!");
            }
            $stmt->close();
            $conn->close();
        } else {
            respond(false, "欄位不得為空!");
        }
    } else {
        respond(false, "欄位錯誤!");
    }
}

// 取得所有會員資料
function get_all_user_data()
{
    $conn  = create_connection();

    $stmt = $conn->prepare("SELECT * FROM member_test ORDER BY id DESC");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        //有資料
        $mydata = [];
        while ($row = $result->fetch_assoc()) {
            unset($row["pwd"]);
            unset($row["Uid01"]);
            $mydata[] = $row;
        }
        respond(true, "取得所有會員資料成功!", $mydata);
    } else {
        respond(false, "查無資料!");
    }
    $stmt->close();
    $conn->close();
}

function get_member_data()
{
    $input = get_json_input();
    $p_id = trim($input["id"]);
    $conn  = create_connection();

    $stmt = $conn->prepare("SELECT * FROM member_test WHERE id = ?");
    $stmt->bind_param("s", $p_id);
    $stmt->execute(); 
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $mydata = [];
        while ($row = $result->fetch_assoc()) {
            unset($row["pwd"]);
            unset($row["Uid01"]);
            $mydata[] = $row;
        }
        respond(true, "取得會員資料成功!", $mydata);
    } else {
        respond(false, "查無資料!");
    }
    $stmt->close();
    $conn->close();
}

// 會員資料更新
function update_user()
{

    if ($_FILES['updateModal_uploadPath']['name'] != "") {
        $uploadPath = get_update_photo();
    } else {
        $uploadPath = $_POST["updateModal_oldphotopath"];
    }

    if (isset($_POST["updateModal_pwd"])) {
        $p_pwd = $_POST["updateModal_pwd"];
    }

    if (isset($_POST["updateModal_pwd"])) {
        $p_pwd = password_hash(trim($_POST["updateModal_pwd"]), PASSWORD_DEFAULT);
    }

    if (isset(
        $_POST["updateModal_id"],
        $_POST["updateModal_username"],
        $_POST["updateModal_tel"],
        $_POST["updateModal_addr"],
        $_POST["updateModal_email"],
        $_POST["updateModal_level"]
    )) {
        $p_id = $_POST["updateModal_id"];
        $p_userName = $_POST["updateModal_username"];
        $p_tel = $_POST["updateModal_tel"];
        $p_addr = $_POST["updateModal_addr"];
        $p_email = $_POST["updateModal_email"];
        $p_level = $_POST["updateModal_level"];

        if ($p_userName && $p_tel && $p_addr && $p_email && $uploadPath && $p_level) {
            $conn = create_connection();
            $sql = "UPDATE member_test SET userName = ?, tel = ?, addr = ?, email = ?, uploadPath = ?, level = ? ";
            if (isset($p_pwd)) {
                $sql .= ", pwd = ?";
            }

            $sql .= " WHERE id = ?";

            $stmt = $conn->prepare($sql);
            if (isset($p_pwd)) {
                $stmt->bind_param("sssssssi", $p_userName, $p_tel, $p_addr, $p_email, $uploadPath, $p_level, $p_pwd, $p_id);
            } else {
                $stmt->bind_param("ssssssi", $p_userName, $p_tel, $p_addr, $p_email, $uploadPath, $p_level, $p_id);
            }


            if ($stmt->execute()) {
                if ($stmt->affected_rows === 1) {
                    respond(true, "會員資料更新成功!");
                } else {
                    respond(false, "會員資料更新失敗, 並無更新行為!");
                }
            } else {
                respond(false, "會員資料更新失敗!");
            }
            $stmt->close();
            $conn->close();
        } else {
            respond(false, "欄位不得為空!");
        }
    } else {
        respond(false, "欄位錯誤!", $_POST["updateModal_level"]);
    }
}

// 會員資料刪除
function delete_user()
{
    $input = get_json_input();
    if (isset($input["id"], $input["uploadpath"])) {
        $p_id         = trim($input["id"]);
        $p_uploadPath = $input["uploadpath"];
        unlink($p_uploadPath);
        if ($p_id) {
            $conn = create_connection();

            $stmt = $conn->prepare("DELETE FROM member_test WHERE id = ?");
            $stmt->bind_param("i", $p_id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows === 1) {

                    respond(true, "會員刪除成功!");
                } else {
                    respond(false, "會員刪除失敗, 並無刪除行為!");
                }
            } else {
                respond(false, "會員刪除失敗!");
            }
            $stmt->close();
            $conn->close();
        } else {
            respond(false, "欄位不得為空!");
        }
    } else {
        respond(false, "欄位錯誤!");
    }
}

// 取得旅遊地區資料
function get_region_process()
{

    $conn = create_connection();
    $sql    = "SELECT region FROM process GROUP BY region";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $mydata = [];
        while ($row = $result->fetch_assoc()) {
            $mydata[] = $row;
        }

        respond(true, "取得旅遊地區成功!", $mydata);
    } else {
        respond(false, "取得旅遊地區失敗!");
    }

    $conn->close();
}

function get_srh_process()
{
    $input = get_json_input();

    $p_startdate = $input["startdate"];
    $p_enddate = $input["enddate"];
    $p_region = "%" . $input["region"] . "%";
    $p_keyword = "%" . $input["keyword"] . "%";

    $conn  = create_connection();
    $sql = "SELECT * FROM process WHERE 1=1"; 
    $params = [];
    $param_types = '';
    if (!empty($p_startdate)) {
        $sql .= " AND startdate >= ?";
        $params[] = $p_startdate;
        $param_types .= "s";
    }

    if (!empty($p_enddate)) {
        $sql .= " AND enddate <= ?";
        $params[] = $p_enddate;
        $param_types .= "s";
    }

    if (!empty($p_region)) {
        $sql .= " AND region LIKE ?";
        $params[] = "%" . $p_region . "%"; 
        $param_types .= "s"; 
    }

    if (!empty($p_keyword)) {
        $sql .= " AND (title LIKE ? OR content LIKE ?)";
        $params[] = "%" . $p_keyword . "%";
        $params[] = "%" . $p_keyword . "%";
        $param_types .= "ss";
    }

    $sql .= " ORDER BY id DESC"; 
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $mydata = [];
        while ($row = $result->fetch_assoc()) {
            $mydata[] = $row;
        }
        respond(true, "取得搜尋資料成功!", $mydata);
    } else {
        respond(false, "查無資料!");
    }
    $stmt->close();
    $conn->close();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'register':
            register_user();
            break;
        case 'login':
            login_user();
            break;
        case 'checkuid':
            check_uid();
            break;
        case 'checkusername':
            check_username();
            break;
        case 'checktel':
            check_tel();
            break;
        case 'checkupdatetel':
            check_update_tel();
            break;
        case 'updateuser':
            update_user();
            break;
        case 'getsrhprocess':
            get_srh_process();
            break;
        case 'getmemberdata':
            get_member_data();
            break;
        default:
            respond(false, "無效的操作");
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'getalldata':
            get_all_user_data();
            break;
        case 'getregionprocess':
            get_region_process();
            break;
        default:
            respond(false, "無效的操作");
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $action = $_GET['action'] ?? '';
    switch ($action) {
        case 'deleteuser':
            delete_user();
            break;
        default:
            respond(false, "無效的操作");
    }
} else {
    respond(false, "無效的請求方法");
}
