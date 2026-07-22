<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>푸른투어 가이드 정산서</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11">  
    <style>
        /* Apple System Font (San Francisco) */
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
            padding-top: 20px;
        }

        /* Custom Table Styles */
        .table-custom-blue {
            background-color: #E1F5FE; /* 옅은 파란색 (Bootstrap info 변형) */
        }
        .table-custom-yellow {
            background-color: #FFFDE7; /* 옅은 노란색 (Bootstrap warning 변형)*/
        }
        .table-header-blue {
            background-color: #0D6EFD; /* 진한 파란색 (Bootstrap primary) */
            color: white;
        }

         .table > :not(caption) > * > * {
            /* 모든 셀에 패딩 일괄 적용 */
            padding: 0.4rem;
            text-align: center; /* 텍스트 가운데 정렬 */
            border: 1px solid #dee2e6;
        }

        .no-border {
            border: none !important;
        }
        .table-no-top-border > thead > tr:first-child > th{
            border-top: 0px;
        }
        .underline{
          text-decoration : underline;
        }

        /* Section Title */
        .section-title {
            border-bottom: 2px solid #0D6EFD; /* 밑줄 스타일 */
            padding-bottom: 5px;
            margin-bottom: 15px;
            font-weight: bold;
        }

        /* Input Fields (for future use, if needed) */
        .form-control-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem; /* Smaller input fields */
        }
         .company-info {
            font-size: 0.9rem; /* Slightly smaller font */
            color: #6c757d;   /* Bootstrap secondary color */
        }

        /* New styles for this section */
        .table-striped > tbody > tr:nth-of-type(odd) {
          --bs-table-accent-bg: var(--bs-table-striped-bg); /* Striped rows */
          color: var(--bs-table-striped-color);
        }
        .table-hover > tbody > tr:hover > * { /* Hover effect */
            color: var(--bs-table-hover-color);
            background-color: var(--bs-table-hover-bg);
        }

        .page-title {
          font-size: 1.5rem;
          font-weight: bold;
          margin-bottom: 1rem;
          text-align: center;

        }
    </style>
</head>
<body>

<div class="container">
    <h2 class="text-center mb-3">푸른투어 가이드 정산서</h2>

    <div class="row mb-3">
        <div class="col-md-8 company-info">
            <div><b>PRT WORLD, INC.</b></div>
            <div>324 Broad Ave</div>
            <div>Ridgefield, NJ 07657</div>
            <div>TEL 201-778-4000</div>
        </div>
        <div class="col-md-4"></div>
    </div>


    <div class="table-responsive">
     <table class="table table-bordered">
            <thead class="table-header-blue">
                <tr>
                    <th>행사날짜 (~)</th>
                    <td><input type="text" class="form-control form-control-sm" value="2024/05/15 ~ 17"></td>
                    <th>투어명</th>
                    <td><input type="text" class="form-control form-control-sm" value="뉴욕 3일 패키지"></td>
                    <th>가이드</th>
                    <td><input type="text" class="form-control form-control-sm" value="홍길동"></td>
                    <th>전반</th>
                    <td><input type="checkbox" class="form-check-input" checked></td>
                    <th>후반</th>
                    <td><input type="checkbox" class="form-check-input"></td>
                </tr>
            </thead>
        </table>


<div class="container">
  <h5 class="section-title">정산 내역</h5>
        <table class="table table-bordered">
            <thead>
                <tr class="table-custom-blue">
                    <th rowspan="2">내역 (손님/여행사)</th>
                    <th rowspan="2">금액</th>
                    <th rowspan="2">카드/현금</th>
                    <th colspan="2" class="table-custom-yellow">기타 입금</th>
                     <th rowspan="2">행사일 및/인원</th>
                    <th rowspan="2">인원</th>
                    <th rowspan="2">행사일 및/인원</th>
                    <th rowspan="2">인원</th>
                </tr>
                <tr class="table-custom-yellow">
                    <th>인원</th>
                    <th>금액</th>

                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><input type="text" class="form-control form-control-sm" value="ABC 여행사 (10명)"></td>
                    <td><input type="text" class="form-control form-control-sm" value="$1000"></td>
                    <td><input type="text" class="form-control form-control-sm" value="카드"></td>
                    <td><input type="text" class="form-control form-control-sm" value="10"></td>
                    <td><input type="text" class="form-control form-control-sm" value="$1000"></td>
                    <td><input type="text" class="form-control form-control-sm" value="2024/05/15">
                      <button type="button" class="btn btn-outline-primary btn-sm add-row-btn">+</button>
                    </td>
                    <td><input type="text" class="form-control form-control-sm" value="15"></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td><input type="text" class="form-control form-control-sm" value="개인 손님 (5명)"></td>
                    <td><input type="text" class="form-control form-control-sm" value="$500"></td>
                    <td><input type="text" class="form-control form-control-sm" value="현금"></td>
                    <td><input type="text" class="form-control form-control-sm" value="5"></td>
                    <td><input type="text" class="form-control form-control-sm" value="$500"></td>
                    <td><input type="text" class="form-control form-control-sm" value="2024/05/16">
                      <button type="button" class="btn btn-outline-primary btn-sm add-row-btn">+</button>
                    </td>
                    <td><input type="text" class="form-control form-control-sm" value="15"></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="table-custom-blue">현지수금액 (투어비)</td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td><input type="text" class="form-control form-control-sm" value="$1500"></td>
                    <td><input type="text" class="form-control form-control-sm" value="2024/05/17">
                      <button type="button" class="btn btn-outline-primary btn-sm add-row-btn">+</button>
                    </td>
                    <td><input type="text" class="form-control form-control-sm" value="15"></td>
                    <td></td>
                    <td></td>
                </tr>
                 <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td> 합계 </td>
                    <td><input type="text" class="form-control form-control-sm" value="45"></td>
                </tr>
                <tr>
                    <td class="fw-bold">TOTAL</td>
                    <td class="fw-bold"><input type="text" class="form-control form-control-sm" value="$1500"></td>
                    <td></td>
                    <td></td>
                    <td class="fw-bold"><input type="text" class="form-control form-control-sm" value="$1500"></td>
                    <td colspan="4"></td>
                </tr>
            </tbody>
        </table>

      </div> <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <script src="script.js"></script> 


        <h5 class="section-title">추가 정산</h5>
         <table class="table table-bordered table-no-top-border">
            <thead>
                <tr class="table-custom-blue">
                    <th>인바운드 (인원X금액)</th>
                    <th>로컬 (인원X금액)</th>
                    <th>가이드 지원금 (인원X금액)</th>
                    <th>비고</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><input type="text" class="form-control form-control-sm" value="15 X $100 = $1500"></td>
                    <td><input type="text" class="form-control form-control-sm"></td>
                    <td><input type="text" class="form-control form-control-sm"></td>
                    <td><input type="text" class="form-control form-control-sm" value="식비 <span class="math-inline">150 별도 청구"\></td\>
</tr\>
<tr class \= "table\-custom\-yellow"\>
<th \>가이드 입금</th\>
<td\></td\>
<td\></td\>
<td\></td\>
</tr\>
<tr\>
<td class \="table\-custom\-yellow underline"\><input type\="text" class\="form\-control form\-control\-sm" value\="500</span>"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>

    <h5 class = "page-title"> 1 페이지 </h5>
    <h5 class="section-title">회사에서 가이드에게 주는 티켓</h5>
       <table class="table table-bordered table-striped table-hover">
        <thead>
            <tr class="table-custom-blue">
                <th>티켓명</th>
                <th>투어인원</th>
                <th>회사 바우처 (수량)</th>
                <th>현금 수금</th>
                <th>현금 수금 내역</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><input type="text" class="form-control form-control-sm" value="원월드 트레이드 센터 전망대"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
            </tr>
            <tr>
                <td><input type="text" class="form-control form-control-sm" value="자유의신상 배"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
            </tr>
            <tr>
                <td><input type="text" class="form-control form-control-sm" value="혼블라워"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
            </tr>
             <tr>
                <td><input type="text" class="form-control form-control-sm" value="전설"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
            </tr>

        </tbody>
    </table>

    <h5 class="section-title">회사 행사 총 지출</h5>
      <table class="table table-bordered">
        <thead>
            <tr class = "table-header-blue">
                <th colspan = "10"> 바우처 </th>
            </tr>
        </thead>
        <tbody>
           <tr>
            <th>이름</th>
           <td><input type="text" class="form-control form-control-sm" ></td>
           <td><input type="text" class="form-control form-control-sm" ></td>
           <td><input type="text" class="form-control form-control-sm" ></td>
           <td><input type="text" class="form-control form-control-sm" ></td>
           <td><input type="text" class="form-control form-control-sm" ></td>
           <td><input type="text" class="form-control form-control-sm" ></td>
           <td><input type="text" class="form-control form-control-sm" ></td>
           <td><input type="text" class="form-control form-control-sm" ></td>
           <td><input type="text" class="form-control form-control-sm" ></td>

           </tr>
            <tr>
                <th>인원X금액</th>
                <td><input type="text" class="form-control form-control-sm" value="X"></td>
                <td><input type="text" class="form-control form-control-sm" value="X"></td>
                <td><input type="text" class="form-control form-control-sm" value="X"></td>
                <td><input type="text" class="form-control form-control-sm" value="X"></td>
                <td><input type="text" class="form-control form-control-sm" value="X"></td>
                <td><input type="text" class="form-control form-control-sm" value="X"></td>
                <td><input type="text" class="form-control form-control-sm" value="X"></td>
                <td><input type="text" class="form-control form-control-sm" value="X"></td>
                <td><input type="text" class="form-control form-control-sm" value="X"></td>

            </tr>

            <tr>
            <th>합계</th>
            <td><input type="text" class="form-control form-control-sm" value="<span class="math-inline">"\></td\>
<td\><input type\="text" class\="form\-control form\-control\-sm" value\="</span>"></td>
            <td><input type="text" class="form-control form-control-sm" value="<span class="math-inline">"\></td\>
<td\><input type\="text" class\="form\-control form\-control\-sm" value\="</span>"></td>
            <td><input type="text" class="form-control form-control-sm" value="<span class="math-inline">"\></td\>
<td\><input type\="text" class\="form\-control form\-control\-sm" value\="</span>"></td>
            <td><input type="text" class="form-control form-control-sm" value="<span class="math-inline">"\></td\>
<td\><input type\="text" class\="form\-control form\-control\-sm" value\="</span>"></td>
            <td><input type="text" class="form-control form-control-sm" value="$"></td>
           </tr>
           <tr class = "table-custom-blue">
             <th colspan = "10">결제 방법</th>
           </tr>

            <tr>
                <td class="fw-bold" colspan = "10">TOTAL &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; $</td>
            </tr>
        </tbody>
    </table>


    <h5 class="section-title">쇼핑</h5>
     <table class="table table-bordered">
        <thead>
            <tr class="table-custom-blue">
                <th>내용</th>
                <th>날짜</th>
                <th>시간</th>
                <th>금액</th>
                 <th>판매량</th>
                <th>쇼핑 수입</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><input type="text" class="form-control form-control-sm" value="와인"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
				<td><input type="text" class="form-control form-control-sm"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
            </tr>
            <tr>
                <td><input type="text" class="form-control form-control-sm" value="쇼핑"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
                <td><input type="text" class="form-control form-control-sm"></td>
            </tr>
            <tr>
               <td class = "fw-bold" colspan = "5"> TOTAL</td>
               <td><input type = "text" class = "form-control form-control-sm" value = "$"></td>
            </tr>
        </tbody>
    </table>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() { // DOMContentLoaded: 페이지 로드 후 실행

    // 1. '+' 버튼에 이벤트 리스너 추가 (이벤트 위임 방식)
    document.querySelector('.table-responsive').addEventListener('click', function(event) {
        if (event.target.classList.contains('add-row-btn')) {
            addRow(event.target); // '+' 버튼을 누른 그 요소(td)를 addRow 함수에 전달
        } else if (event.target.classList.contains('delete-row-btn')) { //삭제 버튼 클릭시
             deleteRow(event.target);
        }
    });

     //input 필드에 change, keyup 이벤트 리스너 추가 (이벤트 위임)
    document.querySelector('.table-responsive').addEventListener('input', function(event){
      if(event.target.tagName === 'INPUT'){
        calculateTotals(); //입력이 있을 때마다 합계 다시계산
      }
    });

    // 초기 합계 계산 (페이지 로드 시)
    calculateTotals();


    // 2. 행 추가 함수
    function addRow(buttonElement) {
        const currentRow = buttonElement.closest('tr'); // '+' 버튼이 속한 현재 행(tr)
        const newRow = currentRow.cloneNode(true); // 현재 행 복사 (deep copy: 자식 요소까지 모두)

        // 새 행 초기화: 입력 필드 내용 지우기,  '+' 버튼 관련 처리
        newRow.querySelectorAll('input[type="text"]').forEach(input => input.value = '');
        newRow.querySelector('.add-row-btn').remove(); // + 버튼은 제거

        // x (삭제) 버튼 추가
        const deleteBtnTd = document.createElement('td');
        deleteBtnTd.innerHTML = '<button type="button" class="btn btn-outline-danger btn-sm delete-row-btn">x</button>';
         newRow.querySelectorAll('td').forEach(td => {
            if (!td.querySelector('input')) { //input이 없는 td
              td.remove(); // 빈 td들 제거
            }
         });

        newRow.appendChild(deleteBtnTd);

        // 현재 행 *아래*에 새 행 삽입
        currentRow.parentNode.insertBefore(newRow, currentRow.nextSibling);
    }


    //행 삭제 함수
    function deleteRow(buttonElement){
        const rowToDelete = buttonElement.closest('tr');

        //sweetalert2 라이브러리 사용 (https://sweetalert2.github.io/)
         Swal.fire({
            title: '정말 삭제하시겠습니까?',
            text: "삭제된 행은 복구할 수 없습니다.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '삭제',
            cancelButtonText: '취소'
        }).then((result) => {
            if (result.isConfirmed) {
               rowToDelete.remove();
               calculateTotals(); //삭제 후 합계 업데이트
            }
        });
    }



    // 총계 계산 함수
    function calculateTotals() {
        let totalAmount = 0;
        let totalOtherAmount = 0;
        let totalPeople = 0;

        // 모든 행 순회하면서 합계 계산
        document.querySelectorAll('.table-responsive table:nth-of-type(2) tbody tr').forEach(row => { //두 번째 테이블 (정산내역)
          //console.log(row);
            const amountInput = row.querySelector('td:nth-child(2) input');
            const otherAmountInput = row.querySelector('td:nth-child(5) input');
            const peopleInput = row.querySelector('td:last-child input'); //마지막 input이 숫자.


             //각 필드의 값이 유효한 숫자인지 확인
            const amount = parseFloat(amountInput ? amountInput.value.replace(/[^0-9.-]+/g,"") : 0) || 0;  //숫자가 아니면 0
            const otherAmount = parseFloat(otherAmountInput ? otherAmountInput.value.replace(/[^0-9.-]+/g,"") : 0) || 0;
            const people = parseInt(peopleInput ? peopleInput.value : 0, 10) || 0; // 10진수, 정수

            totalAmount += amount;
            totalOtherAmount += otherAmount;
            totalPeople += people; // 인원수 합계

        });

         // 인바운드 합계
          document.querySelectorAll('.table-responsive table:nth-of-type(3) tbody tr:nth-child(1) td').forEach((td, index) => {
                if(index === 0) return; //첫번째 td는 스킵

                const input = td.querySelector('input');
                if(input){
                  input.value =  totalPeople + " X $" + (index * 100) + " = $" + (totalPeople * (index*100));
                }

          });

          // 가이드 입금액 입력 필드 (세 번째 테이블의 네 번째 행, 첫 번째 셀)
          const guideDepositInput = document.querySelector('.table-responsive table:nth-of-type(3) tbody tr:nth-child(4) td:nth-child(1) input');
          const guideDepositAmount = parseFloat(guideDepositInput.value.replace(/[^0-9.-]+/g, "")) || 0;

        // 결과 업데이트 (두 번째 테이블의 마지막 행)
        document.querySelector('.table-responsive table:nth-of-type(2) tbody tr:last-child td:nth-child(2) input').value = '$' + totalAmount.toFixed(0);
        document.querySelector('.table-responsive table:nth-of-type(2) tbody tr:last-child td:nth-child(5) input').value = '$' + totalOtherAmount.toFixed(0);
        document.querySelector('.table-responsive table:nth-of-type(2) tbody tr:nth-last-child(2) td:nth-child(3) input').value = totalPeople;
        document.querySelector('.table-responsive table:nth-of-type(3) tr:last-child td:first-child input').value = '$' + (totalAmount-guideDepositAmount).toFixed(0); //세 번째 테이블 최종

    }

});
</script>
</body>
</html>