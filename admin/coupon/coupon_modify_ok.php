<?php 
    // Enable error reporting
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    // Set error log file
    ini_set('error_log', __DIR__ . '/coupon_modify_error.log');

    session_start();
    
    // Detailed logging function
    function logError($message) {
        error_log($message);
        file_put_contents(__DIR__ . '/coupon_modify_debug.log', 
            date('[Y-m-d H:i:s] ') . $message . PHP_EOL, 
            FILE_APPEND
        );
    }

    // Check authentication
    if(!isset($_SESSION['AUID'])){
        logError('Authentication failed: No AUID in session');
        echo "<script>
                alert('접근 권한이 없습니다');
                history.back();
              </script>";
        exit;
    }

    // Include database connection using relative path
    include __DIR__ . '/../../inc/db.php';

    // Function to clean numeric input and handle percentage
    function cleanNumericInput($input) {
        // Remove % and any non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $input);
        return $cleaned ? (int)$cleaned : null;
    }

    // Log all POST data for debugging
    logError('POST Data: ' . print_r($_POST, true));
    logError('FILES Data: ' . print_r($_FILES, true));

    try {
        // Validate and sanitize input
        $cno = filter_input(INPUT_POST, 'cid', FILTER_VALIDATE_INT);
        $coupon_name = trim(strip_tags($_POST['coupon_name'])); 
        $coupon_type = filter_input(INPUT_POST, 'coupon_type', FILTER_VALIDATE_INT);
        
        // Clean numeric inputs with percentage handling
        $coupon_discount = cleanNumericInput($_POST['coupon_discount']);
        $coupon_ratio = cleanNumericInput($_POST['coupon_ratio']);
        $status = filter_input(INPUT_POST, 'status', FILTER_VALIDATE_INT);
        $max_price = cleanNumericInput($_POST['max_price']);
        $min_price = cleanNumericInput($_POST['min_price']);
        $coupon_due = filter_input(INPUT_POST, 'coupon_due', FILTER_VALIDATE_INT);

        // Validate required fields
        $errors = [];
        if (!$cno) $errors[] = 'CID';
        if (empty($coupon_name)) $errors[] = '쿠폰명';
        if (!$coupon_type) $errors[] = '쿠폰타입';
        if (!$coupon_discount) $errors[] = '할인가';
        if (!$coupon_ratio) $errors[] = '할인율';
        if (!$status) $errors[] = '상태';
        if (!$max_price) $errors[] = '최대사용금액';
        if (!$min_price) $errors[] = '최소사용금액';
        if (!$coupon_due) $errors[] = '사용기한';

        if (!empty($errors)) {
            logError('Validation errors: ' . implode(', ', $errors));
            echo "<script>
                    alert('다음 필드를 확인해주세요: " . implode(', ', $errors) . "');
                    history.back();
                  </script>";
            exit;
        }

        // Handle date logic
        $start_date = $end_date = null;
        if ($coupon_due == 2) {
            $start_date = trim(strip_tags($_POST['coupon_start_date']));
            $end_date = trim(strip_tags($_POST['coupon_end_date']));
            
            if (!$start_date || !$end_date) {
                logError('Date validation failed');
                echo "<script>
                        alert('시작일과 종료일을 입력해주세요.');
                        history.back();
                      </script>";
                exit;
            }
        }

        // Handle file upload
        $coupon_image = null;
        if (!empty($_FILES['file']['name'])) {
            // File validation
            if ($_FILES['file']['size'] > 10240000) {
                logError('File size too large');
                echo "<script>alert('10메가 이하만 첨부할 수 있습니다.');history.back();</script>";
                exit;
            }

            $allowed_types = ['image/jpeg', 'image/gif', 'image/png'];
            if (!in_array($_FILES['file']['type'], $allowed_types)) {
                logError('Invalid file type');
                echo "<script>alert('이미지만 첨부할 수 있습니다.');history.back();</script>";
                exit;
            }

            // File save logic
            $save_dir = __DIR__ . '/coupon_image/';
            $filename = $_FILES["file"]["name"];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $newfilename = "coupon_" . date("YmdHis") . substr(rand(), 0, 6);
            $coupon_image = $newfilename . "." . $ext;
            
            if (move_uploaded_file($_FILES["file"]["tmp_name"], $save_dir . $coupon_image)) {
                $coupon_image = "/admin/coupon/coupon_image/" . $coupon_image;
                logError('File uploaded successfully: ' . $coupon_image);
            } else {
                logError('File upload failed');
                echo "<script>alert('이미지를 등록할 수 없습니다. 관리자에게 문의해주십시오.');history.back();</script>";
                exit;
            }
        }

        // First, fetch the existing row to get current file if no new file is uploaded
        $existing_stmt = $mysqli->prepare("SELECT file FROM coupons WHERE cid = ?");
        $existing_stmt->bind_param("i", $cno);
        $existing_stmt->execute();
        $existing_result = $existing_stmt->get_result();
        $existing_row = $existing_result->fetch_assoc();

        // Determine file parameter
        $file_param = $coupon_image ?: $existing_row['file'];
        logError('File parameter: ' . ($file_param ?? 'NULL'));

        // Prepare SQL statement
        $stmt = $mysqli->prepare("UPDATE coupons 
            SET coupon_name = ?, 
                coupon_type = ?, 
                coupon_discount = ?, 
                coupon_ratio = ?, 
                status = ?, 
                max_price = ?, 
                min_price = ?, 
                coupon_due = ?, 
                coupon_start_date = ?, 
                coupon_end_date = ?, 
                file = ? 
            WHERE cid = ?");
        
        // Ensure all variables are defined
        $bind_start_date = $start_date ?? null;
        $bind_end_date = $end_date ?? null;

        // Bind parameters with variables
        $bind_name = $coupon_name;
        $bind_type = $coupon_type;
        $bind_discount = $coupon_discount;
        $bind_ratio = $coupon_ratio;
        $bind_status = $status;
        $bind_max_price = $max_price;
        $bind_min_price = $min_price;
        $bind_due = $coupon_due;
        $bind_file = $file_param;
        $bind_cno = $cno;

        // Validate bind parameters
        $bind_types = "sisisissssssi";
        $bind_vars = [
            $bind_name, 
            $bind_type, 
            $bind_discount, 
            $bind_ratio, 
            $bind_status, 
            $bind_max_price, 
            $bind_min_price, 
            $bind_due, 
            $bind_start_date, 
            $bind_end_date, 
            $bind_file,
            $bind_cno
        ];

        // Validate bind parameter count
        if (strlen($bind_types) !== count($bind_vars)) {
            // Truncate type string if it's longer
            $bind_types = substr($bind_types, 0, count($bind_vars));
        }

        // Bind parameters
        $result = $stmt->bind_param(
            $bind_types, 
            $bind_vars[0], 
            $bind_vars[1], 
            $bind_vars[2], 
            $bind_vars[3], 
            $bind_vars[4], 
            $bind_vars[5], 
            $bind_vars[6], 
            $bind_vars[7], 
            $bind_vars[8], 
            $bind_vars[9], 
            $bind_vars[10],
            $bind_vars[11]
        );

        // Detailed logging of bind parameters
        logError('Bind Types (adjusted): ' . $bind_types);
        logError('Bind Params: ' . json_encode([
            'coupon_name' => $bind_vars[0],
            'coupon_type' => $bind_vars[1],
            'coupon_discount' => $bind_vars[2],
            'coupon_ratio' => $bind_vars[3],
            'status' => $bind_vars[4],
            'max_price' => $bind_vars[5],
            'min_price' => $bind_vars[6],
            'coupon_due' => $bind_vars[7],
            'start_date' => $bind_vars[8],
            'end_date' => $bind_vars[9],
            'file_param' => $bind_vars[10],
            'cno' => $bind_vars[11]
        ]));

        if ($result === false) {
            logError('Bind param failed: ' . $stmt->error);
            throw new Exception("Failed to bind parameters: " . $stmt->error);
        }

        $execute_result = $stmt->execute();

        if ($execute_result) {
            logError('Coupon update successful');
            echo "<script> 
                    alert('쿠폰 수정이 완료되었습니다.');
                    location.href = './coupon_list.php';
                  </script>";
        } else {
            logError('Coupon update failed: ' . $stmt->error);
            echo "<script> 
                    alert('쿠폰 수정에 실패했습니다: " . $stmt->error . "');
                    history.back();
                  </script>";
        }
    } catch (Exception $e) {
        logError('Exception occurred: ' . $e->getMessage());
        echo "<script> 
                alert('오류가 발생했습니다: " . $e->getMessage() . "');
                history.back();
              </script>";
        exit;
    }
?>