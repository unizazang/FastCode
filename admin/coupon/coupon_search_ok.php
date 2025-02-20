<?php 
    session_start();
    
    // Include database connection
    include __DIR__ . '/../../inc/db.php';

    // Function to sanitize image path (moved outside the loop)
    function sanitizeImagePath($file) {
        if (empty($file)) return '';
        
        // If the path doesn't start with /pdata/ or /admin/coupon/coupon_image/ or coupon_image/
        if (strpos($file, '/pdata/') !== 0 && 
            strpos($file, '/admin/coupon/coupon_image/') !== 0 && 
            strpos($file, 'coupon_image/') !== 0) {
            // Prepend coupon_image/ to the basename
            return './coupon_image/' . basename($file);
        }
        
        // If path already starts with ./
        if (strpos($file, './') === 0) {
            return $file;
        }
        
        // Prepend ./ to paths that don't start with it
        return './' . $file;
    }

    // Authentication check using original method
    if(!$_SESSION['AUID']){
      echo "<script>
              alert('접근 권한이 없습니다');
              history.back();
          </script>";
      exit;
    }

    // Validate and sanitize search keyword
    $keyword = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
    if (empty($keyword)) {
        echo "<script>
                alert('검색어를 입력해주세요.');
                history.back();
            </script>";
        exit;
    }

    // Validate page number
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?? 1;
    $page = max(1, $page);  // Ensure page is at least 1

    /* ================== 페이지네이션 =================== */
    $list = 5;  // Items per page
    $block_ct = 5;  // Number of page links to show

    try {
        // Count total results
        $searchStmt = $mysqli->prepare("SELECT COUNT(*) as cnt FROM coupons WHERE coupon_name LIKE ?");
        $searchKeyword = "%{$keyword}%";
        $searchStmt->bind_param("s", $searchKeyword);
        $searchStmt->execute();
        $page_result = $searchStmt->get_result();
        $page_row = $page_result->fetch_assoc();
        $row_num = $page_row['cnt'];

        $block_num = ceil($page/$block_ct);
        $block_start = (($block_num - 1) * $block_ct) + 1; 
        $block_end = $block_start + $block_ct - 1; 

        $total_page = ceil($row_num/$list);
        if($block_end > $total_page) $block_end = $total_page;
        $total_block = ceil($total_page/$block_ct);
        $start_num = ($page - 1) * $list;

        // Fetch results
        $stmt = $mysqli->prepare("SELECT * FROM coupons WHERE coupon_name LIKE ? ORDER BY cid DESC LIMIT ?, ?");
        $stmt->bind_param("sii", $searchKeyword, $start_num, $list);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $rsc = [];
        while ($rs = $result->fetch_object()) {
            $rsc[] = $rs;
        }
    } catch (Exception $e) {
        error_log("Search Error: " . $e->getMessage());
        echo "<script>
                alert('검색 중 오류가 발생했습니다.');
                history.back();
            </script>";
        exit;
    }

    // Updated includes to use relative paths
    include __DIR__ . '/../../inc/head.php';
?>

<link rel="stylesheet" href="../css/coupon_delete.css" />
<link rel="stylesheet" href="../css/coupon_list.css" />

<?php     
    include __DIR__ . '/../../inc/common.php'; 
?>

</div>
<!-- 로고 및 북마크 위치 끝 -->

<!-- 본문시작 -->

<h2 class="page-title"><?= htmlspecialchars($keyword) ?> 검색 결과</h2>

<div class="coupon_top">
  <a href="./coupon_up.php" class="y-btn big-btn btn-navy">쿠폰추가하기</a>
  <form action="./coupon_search_ok.php" method="GET" class="coupon_search">
    <input
      class="form-control"
      type="search"
      name="search"
      placeholder="검색어를 입력하세요."
      required
    />
    <button
      type="submit"
      id="search"
      class="col-md-2 y-btn mid-btn btn-sky">
      검색하기
    </button>
  </form>
</div>

  <!-- list 수정 0320 -->
  <ul>
    <?php
            if(!empty($rsc)){
                foreach($rsc as $r){ //조회된 쿠폰 출력
              
        ?>    
        <li id="<?= $r->cid;?>"  class="coupon_list">
            <figure>
                <img src="./<?= htmlspecialchars(sanitizeImagePath($r -> file)); ?>" alt="<?= htmlspecialchars($r -> coupon_name); ?>" />
            </figure>
            <div class="titles">
                <div class="big_titles">
                    <h3 class="lititle"><?= htmlspecialchars($r->coupon_name);?></h3>
                   <?php 
                            // 태그 =================================
                            $registered_time = $r->regdate; //쿠폰 등록날짜
                            $now = date('Y-m-d'); //오늘날짜

                            // ====== new ======
                            if($registered_time == $now){
                                $newtag = '<a class="mini-tag new-tag">new</a>';
                            } else{
                                $newtag = '';
                            }

                            // ====== unlimited ======
                            if($r->coupon_due == 1){// 날짜 같으면
                              $unlimittag = '<a class="mini-tag limit-tag">무제한</a>';
                            } else{
                                $unlimittag = '';
                            }

                            echo $newtag;
                            echo $unlimittag;

                          ?>  
                </div>
                <div class="sub_titles">
                    <p>최소금액 : <span><?= number_format($r->min_price); ?>원 이상</span></p>
                    <p>할인율 : <span><?= htmlspecialchars($r->coupon_ratio); ?></span></p>
                </div>
            </div>
            <div class="coupon_select">
                <select class="form-select" name="coupon" id="coupon">
                    <option value="1" <?php if($r->status == 1) echo "selected"; ?>>활성화</option>
                    <option value="0" <?php if($r->status == 0) echo "selected"; ?> >비활성화</option>
                </select>
            </div>
            <div class="btns">
                <a href="./coupon_modify.php?cid=<?= $r->cid; ?>" class="y-btn small-btn btn-navy">수정하기</a>
                <button class="y-btn small-btn btn-red del">삭제하기</button>
            </div>
        </li>

        <?php } } else { ?>
              <!-- 검색결과없을때 -->
              <li class="coupon_list d-flex align-items-center justify-content-center">
                <div class="big_titles text-center">해당하는 쿠폰이 없습니다.</div>
              </li>
              <?php } ?>
</ul>
<!-- 페이지네이션 -->
<div class="coupon_pagination row">
  <ul class="row col justify-content-center">
            <?php 
            // Add next page navigation
            if($block_num > 1){
                $prev = ($block_num - 2)*$block_ct + 1;
                echo "<li class='col-auto'><a href='?search=" . urlencode($keyword) . "&page=$prev'><i class='fa-solid fa-chevron-left'></i></a></li>";
            }

            for($i=$block_start; $i<= $block_end; $i++){
                if($page == $i){
                    echo "<li class='col-auto'><a href='?search=" . urlencode($keyword) . "&page=$i' class='active'>$i</a></li>";
                }else{
                    echo "<li class='col-auto'><a href='?search=" . urlencode($keyword) . "&page=$i'>$i</a></li>";
                }
            }

            // Add next page navigation
            if($page < $total_page){
                $next = $page + 1;
                echo "<li class='col-auto'><a href='?search=" . urlencode($keyword) . "&page=$next'><i class='fa-solid fa-chevron-right'></i></a></li>";
            }
            ?>
  </ul>
</div>

<!-- 본문끝 -->

        <!-- 삭제 팝업 HTML -->
        <div class="background">
          <div class="window">
            <div class="popup">
              <div class="flex">
                <p class="title">글을 삭제하시겠습니까?</p>
                <input type="text" placeholder="">
                <div class="popup_btns">
                  <a id="close" class="y-btn big-btn btn-sky">취소하기</a>
                  <a class="y-btn big-btn btn-red" id="deletebtn">삭제하기</a>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- 팝업 HTML 끝 -->

<?php
  include __DIR__ . '/../../inc/footer.php';
?>
<script
  src="https://code.jquery.com/jquery-3.6.3.min.js" integrity="sha256-pvPw+upLPUjgMXY0G+8O0xUf+/Im1MZjXxxgOcBQBXU=" crossorigin="anonymous">
</script>
<script src="../board/functions.js"></script>

<script>
  // 삭제 버튼(바깥)을 누르면 할일
  $(".del").click(function(){
    let row = $(this).closest('li');
    let idx = row.attr('id');
    let title = row.find('.lititle').text();
    
    // 모달 보이기
    $(".background").addClass('show');
    $(".background input[type='text']").val(title);
    $("#deletebtn").data('idx', idx);
  });

  $("#close").click(function(){
    $(".background").removeClass('show');
  });

  //삭제하시겠습니까? 안쪽 삭제 버튼 누르면 할일
  $('#deletebtn').click(function(){
    let idx = $(this).data('idx');

    let data = {
      idx: idx,
    }
    delAjax(idx, './coupon_delete.php', './coupon_list.php')
  });

  // 쿠폰 상태 변경 기능
  $(".coupon_select .form-select").change(function(){
    let selectedStatus = $(this).find("option:selected").val();
    let selectedidx = $(this).closest('li').attr('id');

    let data = {
      selectedStatus: selectedStatus,
      selectedidx: selectedidx
    };

    $.ajax({
      async: false,
      type: 'post',
      url: './coupon_option_change.php',
      data: data,
      dataType: 'json',
      error: function() {
        alert('상태 변경 중 오류가 발생했습니다.');
      },
      success: function(result) {
        if (result.result === true) {
          alert('쿠폰 상태가 변경되었습니다.');
        } else {
          alert('상태 변경에 실패했습니다.');
          // 실패 시 선택 원복
          $(".coupon_select .form-select").val(selectedStatus === '1' ? '0' : '1');
        }
      }
    });
  });
</script>

<?php 
    include __DIR__ . '/../../inc/foot.php';
 ?>