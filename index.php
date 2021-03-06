
<?php
require('simple_html_dom.php');
define('FINANCIAL_MAX_ITERATIONS', 128);
define('FINANCIAL_PRECISION', 1.0e-08);
if(isset($_GET["macp"])){
$year = date("Y");
$macp=$_GET["macp"];

$rowData1=html_to_array("http://finance.tvsi.com.vn/Enterprises/ChiTieuQuanTrong?symbol=".$macp."&period=0&currentPage=1&donvi=1");
$rowData2=html_to_array("http://finance.tvsi.com.vn/Enterprises/BangCanDoiKeToan?symbol=".$macp."&YearView=".$year."&period=2&donvi=1");
$rowData3 = html_to_array("http://finance.tvsi.com.vn/Enterprises/BaoCaoKetQuaKd?symbol=".$macp."&YearView=".$year."&period=2&donvi=1");
$rowData4 = html_to_array("https://finance.tvsi.com.vn/Enterprises/LuuChuyenTienTegiantiep?symbol=".$macp."&YearView=".$year."&period=2&donvi=1");

$nowEPS=$rowData1[search_arr("EPS",$rowData1)][6];
$nowNodaihan=$rowData2[search_arr("Nợ dài hạn",$rowData2)][6];
$nowThunhapsauthue=$rowData3[search_arr("Lãi/(lỗ) thuần sau thuế",$rowData3)][6];
$nowVonchu=$rowData2[search_arr("Vốn chủ sở hữu",$rowData2)][6];


$array = array();

$array['Name'] = LayTenCty($macp);
$array['Code'] = $macp;
$array['Price'] = LayGiaCophieu($macp);
//$array['Amount'] = $rowData5[0][3];

$array['ROIC_calc'] = array
(
  'EPS' => $nowEPS,
  'No_dai_han' => $nowNodaihan,
  'Thu_nhap_sau_thue' => $nowThunhapsauthue,
  'ROIC' => (round(ROIC(_int($nowThunhapsauthue),_int($nowVonchu),_int($nowNodaihan)), 2, PHP_ROUND_HALF_UP)."%")
);


$array['ChiTieuQuanTrong'] = $rowData1;
$array['BangCanDoiKeToan'] = $rowData2;
$array['BaoCaoKetQuaKd'] = $rowData3;
$array['LuuChuyenTienTegiantiep'] = $rowData4;
$json = json_encode($array, JSON_PRETTY_PRINT);


echo $json;
//echo "Tên doanh nghiệp: ".LayTenCty($macp)."</br>";
//echo "Mã cỗ phiếu: ".strtoupper($macp)."</br>";
//echo "Sl cỗ phiếu đang lưu hành :".$rowData5[0][3]." (cỗ phiếu)</br>";
//echo "Giá hiện tại: ".LayGiaCophieu($macp)." (đồng)</br>";
//echo "EPS HIỆN TẠI :".$nowEPS." (đồng)</br>";
//echo "NỢ DÀI HẠN :".$nowNodaihan." (đồng)</br>";
//echo "Thu nhập sau thuế :".$nowThunhapsauthue." (đồng)</br>";
//echo "Vốn chủ kì gần nhất :".$nowVonchu." (đồng)</br>";
//echo "Tỉ lệ tăng trưởng dòng tiền(3 năm) :".round(RATE(2,0,-_int($rowData4[19][4]),_int($rowData4[19][6]))*100, 2, PHP_ROUND_HALF_UP)."%</br>";
//echo "ROIC :".round(ROIC(_int($nowThunhapsauthue),_int($nowVonchu),_int($nowNodaihan)), 2, PHP_ROUND_HALF_UP)."% </br>";

//echo "#TỔNG QUAN: </br>";
//echo_table_array($rowData5);
//echo "#CÁC CHỈ TIÊU QUANG TRONG(5 quý gần nhất): </br>";
//echo_table_array($rowData1);
//echo "#BẢNG CÂN ĐỐI KẾ TOÁN(5 quý gần nhất): </br>";
//echo_table_array($rowData2);
//echo "#BÁO CÁO KẾT QUẢ KINH DOANH: </br>";
//echo_table_array($rowData3);
//echo "#LƯU CHUYỂN TIỀN TỆ GIÁN TIẾP: </br>";
//echo_table_array($rowData4);
}
function search_arr($find_string, $array) {
   for ($i=0; $i < sizeof($array) ; $i++) {
       if (strpos($array[$i][0], $find_string)!== false) {
           return $i;
       }
   }
   return null;
}
function RATE($nper, $pmt, $pv, $fv = 0.0, $type = 0, $guess = 0.1) {

    $rate = $guess;
    if (abs($rate) < FINANCIAL_PRECISION) {
        $y = $pv * (1 + $nper * $rate) + $pmt * (1 + $rate * $type) * $nper + $fv;
    } else {
        $f = exp($nper * log(1 + $rate));
        $y = $pv * $f + $pmt * (1 / $rate + $type) * ($f - 1) + $fv;
    }
    $y0 = $pv + $pmt * $nper + $fv;
    $y1 = $pv * $f + $pmt * (1 / $rate + $type) * ($f - 1) + $fv;

    // find root by secant method
    $i  = $x0 = 0.0;
    $x1 = $rate;
    while ((abs($y0 - $y1) > FINANCIAL_PRECISION) && ($i < FINANCIAL_MAX_ITERATIONS)) {
        $rate = ($y1 * $x0 - $y0 * $x1) / ($y1 - $y0);
        $x0 = $x1;
        $x1 = $rate;

        if (abs($rate) < FINANCIAL_PRECISION) {
            $y = $pv * (1 + $nper * $rate) + $pmt * (1 + $rate * $type) * $nper + $fv;
        } else {
            $f = exp($nper * log(1 + $rate));
            $y = $pv * $f + $pmt * (1 / $rate + $type) * ($f - 1) + $fv;
        }

        $y0 = $y1;
        $y1 = $y;
        ++$i;
    }
    return $rate;
}   //  function RATE()
function LayTenCty($macp){
return json_decode(file_get_html("https://finance.tvsi.com.vn/TvsiAjax/GetMaChungkhoan?q=".$macp),true)["data"][0]["tendoanhnghiep"];
}
function LayGiaCophieu($macp){
return json_decode(file_get_html("http://e.cafef.vn/info.ashx?type=cp&symbol=".$macp),true)["Price"];
}
function ROIC($LN,$VC,$NDH){
	return $LN/($VC+$NDH)*100;
}
function _int($string){
	return strval(str_replace(',','',$string)); 
}
function echo_table_array($rowData){
	echo '<table border="1">';
foreach ($rowData as $row => $tr) {
    echo '<tr>';
    foreach ($tr as $td)
        echo '<td>' . $td .'</td>';
    echo '</tr>';
}
echo '</table>';
}
function html_to_array($url,$thutu=0){
	$html = file_get_html($url);

$table = $html->find('table');
$rowData = array();

foreach($table[$thutu]->find('tr') as $row) {
    // initialize array to store the cell data from each row
    $flight = array();
    foreach($row->find('td') as $cell) {
        // push the cell's text to the array
        $flight[] = $cell->plaintext;
    }
    $rowData[] = $flight;
}
return $rowData;
}

?>
