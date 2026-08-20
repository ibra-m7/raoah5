<?php

namespace App\Support\Excel;

final class SpreadsheetXml
{
    /**
     * @param  list<array{name: string, rows: list<list<array{value: string, style?: string, type?: string}>>, freeze?: bool, widths?: list<int>}>  $sheets
     */
    public static function document(array $sheets, array $styles = []): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>'."\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">';
        $xml .= '<DocumentProperties xmlns="urn:schemas-microsoft-com:office:office"><Author>روعة الخمسة</Author></DocumentProperties>';
        $xml .= '<Styles>';
        $xml .= '<Style ss:ID="Default"><Alignment ss:Horizontal="Right" ss:ReadingOrder="RightToLeft" ss:WrapText="1"/><Font ss:FontName="Cairo" ss:Size="11"/></Style>';
        foreach ($styles as $id => $inner) {
            $xml .= '<Style ss:ID="'.self::esc($id).'">'.$inner.'</Style>';
        }
        $xml .= '</Styles>';

        foreach ($sheets as $sheet) {
            $xml .= '<Worksheet ss:Name="'.self::esc($sheet['name']).'">';
            $xml .= '<Table>';
            foreach ($sheet['widths'] ?? [] as $width) {
                $xml .= '<Column ss:AutoFitWidth="0" ss:Width="'.$width.'"/>';
            }
            foreach ($sheet['rows'] as $row) {
                $xml .= '<Row ss:AutoFitHeight="1" ss:Height="22">';
                foreach ($row as $cell) {
                    $style = isset($cell['style']) ? ' ss:StyleID="'.self::esc($cell['style']).'"' : '';
                    $type = $cell['type'] ?? 'String';
                    $xml .= '<Cell'.$style.'><Data ss:Type="'.$type.'">'.self::esc((string) $cell['value']).'</Data></Cell>';
                }
                $xml .= '</Row>';
            }
            $xml .= '</Table>';
            $xml .= '<WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><DisplayRightToLeft/>';
            if (! empty($sheet['freeze'])) {
                $xml .= '<FreezePanes/><FrozenNoSplit/><SplitHorizontal>1</SplitHorizontal><TopRowBottomPane>1</TopRowBottomPane><ActivePane>2</ActivePane>';
            }
            $xml .= '</WorksheetOptions></Worksheet>';
        }

        $xml .= '</Workbook>';

        return $xml;
    }

    public static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
