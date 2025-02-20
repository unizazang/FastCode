<?php
    session_start();
    
    // Include database connection
    include __DIR__ . '/../../inc/db.php';

    // Improved authentication check
    if (!isset($_SESSION['AUID'])) {
        $returned_data = ['result' => false, 'message' => '접근 권한이 없습니다'];
        echo json_encode($returned_data);
        exit;
    }

    // Validate input
    $cid = filter_input(INPUT_POST, 'idx', FILTER_VALIDATE_INT);
    
    if (!$cid) {
        $returned_data = ['result' => false, 'message' => '잘못된 쿠폰 번호입니다'];
        echo json_encode($returned_data);
        exit;
    }

    try {
        // Use prepared statement for deletion
        $sql = "DELETE FROM coupons WHERE cid = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $cid);
        
        // Execute deletion
        if ($stmt->execute()) {
            // Optional: Delete associated file if it exists
            $file_query = "SELECT file FROM coupons WHERE cid = ?";
            $file_stmt = $mysqli->prepare($file_query);
            $file_stmt->bind_param("i", $cid);
            $file_stmt->execute();
            $file_result = $file_stmt->get_result();
            $file_row = $file_result->fetch_assoc();

            if (!empty($file_row['file'])) {
                $file_path = __DIR__ . '/coupon_image/' . basename($file_row['file']);
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }

            $returned_data = ['result' => true, 'message' => '쿠폰이 삭제되었습니다'];
        } else {
            $returned_data = ['result' => false, 'message' => '삭제 중 오류가 발생했습니다'];
        }
    } catch (Exception $e) {
        error_log("Coupon Delete Error: " . $e->getMessage());
        $returned_data = ['result' => false, 'message' => '삭제 중 오류가 발생했습니다'];
    }

    // Return JSON response
    echo json_encode($returned_data);
?>