<?php
/**
 * 간단한 Excel 파일 리더
 * ZIP과 XML 파싱 기능만 사용
 */

if (!class_exists('SimpleExcelReader')) {
    class SimpleExcelReader {
        private $data = [];
        private $shared_strings = [];
        
        public static function read($file_path) {
            $reader = new self();
            
            if (!file_exists($file_path)) {
                throw new Exception("파일이 존재하지 않습니다: $file_path");
            }
            
            // ZIP 확장 확인
            if (!class_exists('ZipArchive')) {
                throw new Exception("ZIP 확장이 설치되어 있지 않습니다. 서버 관리자에게 문의하세요.");
            }
            
            $zip = new ZipArchive();
            if ($zip->open($file_path) !== TRUE) {
                throw new Exception("Excel 파일을 열 수 없습니다. 파일이 손상되었을 수 있습니다.");
            }
            
            // Shared Strings 읽기
            $shared_strings_xml = $zip->getFromName('xl/sharedStrings.xml');
            if ($shared_strings_xml !== false) {
                $reader->parseSharedStrings($shared_strings_xml);
            }
            
            // 워크시트 읽기
            $worksheet_xml = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($worksheet_xml === false) {
                $zip->close();
                throw new Exception("워크시트를 찾을 수 없습니다.");
            }
            
            $reader->parseWorksheet($worksheet_xml);
            $zip->close();
            
            return $reader->data;
        }
        
        private function parseSharedStrings($xml_string) {
            // XML 파싱 오류 방지
            libxml_use_internal_errors(true);
            
            $xml = simplexml_load_string($xml_string);
            if ($xml === false) {
                return; // Shared strings가 없어도 처리 가능
            }
            
            foreach ($xml->si as $si) {
                if (isset($si->t)) {
                    $this->shared_strings[] = (string)$si->t;
                } else {
                    $text = '';
                    if (isset($si->r)) {
                        foreach ($si->r as $r) {
                            if (isset($r->t)) {
                                $text .= (string)$r->t;
                            }
                        }
                    }
                    $this->shared_strings[] = $text;
                }
            }
        }
        
        private function parseWorksheet($xml_string) {
            libxml_use_internal_errors(true);
            
            $xml = simplexml_load_string($xml_string);
            if ($xml === false) {
                throw new Exception("워크시트 XML을 파싱할 수 없습니다.");
            }
            
            $rows = [];
            
            if (isset($xml->sheetData->row)) {
                foreach ($xml->sheetData->row as $row) {
                    $row_index = (int)$row['r'] - 1;
                    $row_data = [];
                    
                    if (isset($row->c)) {
                        foreach ($row->c as $cell) {
                            $cell_ref = (string)$cell['r'];
                            $col_index = $this->getColumnIndex($cell_ref);
                            
                            $value = '';
                            if (isset($cell->v)) {
                                $value = (string)$cell->v;
                                
                                // Shared string인 경우
                                if (isset($cell['t']) && (string)$cell['t'] === 's') {
                                    $index = (int)$value;
                                    if (isset($this->shared_strings[$index])) {
                                        $value = $this->shared_strings[$index];
                                    }
                                }
                            } elseif (isset($cell->is->t)) {
                                $value = (string)$cell->is->t;
                            }
                            
                            $row_data[$col_index] = $value;
                        }
                    }
                    
                    // 빈 셀 채우기
                    if (!empty($row_data)) {
                        $max_col = max(array_keys($row_data));
                        for ($i = 0; $i <= $max_col; $i++) {
                            if (!isset($row_data[$i])) {
                                $row_data[$i] = '';
                            }
                        }
                        ksort($row_data);
                        $rows[$row_index] = array_values($row_data);
                    }
                }
            }
            
            ksort($rows);
            $this->data = array_values($rows);
        }
        
        private function getColumnIndex($cell_ref) {
            preg_match('/([A-Z]+)(\d+)/', $cell_ref, $matches);
            $column = $matches[1];
            
            $index = 0;
            for ($i = 0; $i < strlen($column); $i++) {
                $index = $index * 26 + (ord($column[$i]) - ord('A') + 1);
            }
            
            return $index - 1;
        }
    }
}

/**
 * 개선된 파일 처리 함수
 */
function processReservationFileImproved(string $file_path, string $product_code): array {
    $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    
    if ($ext === 'csv') {
        return processCSVFile($file_path, $product_code);
    }
    
    if (in_array($ext, ['xlsx', 'xls'])) {
        try {
            // 간단한 Excel 리더 사용
            $data = SimpleExcelReader::read($file_path);
            return extractReservationData($data, $product_code);
        } catch (Exception $e) {
            // Excel 처리 실패시 CSV 변환 안내
            throw new Exception("
                Excel 파일 처리 중 오류가 발생했습니다: {$e->getMessage()}
                
                해결방법:
                1. Excel에서 파일을 엽니다
                2. '파일' → '다른 이름으로 저장'
                3. '파일 형식'을 'CSV UTF-8 (쉼표로 분리)'로 선택
                4. 저장된 CSV 파일을 업로드해주세요
            ");
        }
    }
    
    throw new Exception("지원하지 않는 파일 형식입니다: {$ext}");
}
?>
