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
    $idx = filter_input(INPUT_POST, 'selectedidx', FILTER_VALIDATE_INT);
    $status = filter_input(INPUT_POST, 'selectedStatus', FILTER_VALIDATE_INT, 
        ['options' => ['min_range' => 0, 'max_range' => 1]]);

    if (!$idx || $status === false) {
        $returned_data = ['result' => false, 'message' => '잘못된 입력값입니다'];
        echo json_encode($returned_data);
        exit;
    }

    try {
        // Use prepared statement for update
        $sql = "UPDATE coupons SET status = ? WHERE cid = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ii", $status, $idx);
        
        if ($stmt->execute()) {
            $returned_data = ['result' => true, 'message' => '상태가 변경되었습니다'];
        } else {
            $returned_data = ['result' => false, 'message' => '상태 변경에 실패했습니다'];
        }
    } catch (Exception $e) {
        error_log("Coupon Status Change Error: " . $e->getMessage());
        $returned_data = ['result' => false, 'message' => '상태 변경 중 오류가 발생했습니다'];
    }

    // Return JSON response
    echo json_encode($returned_data);
?>