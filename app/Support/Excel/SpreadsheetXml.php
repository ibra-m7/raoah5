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
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">';
        $xml .= '<DocumentProperties xmlns="urn:schemas-microsoft-com:office:office"><Author>روعة الخمسة</Author></DocumentProperties>';
        $xml .= '<Styles>';
        $xml .= '<Style ss:ID="Default"><Alignment ss:Horizontal="Right" ss:ReadingOrder="RightToLeft" ss:WrapText="1"/><Font ss:FontName="Cairo" ss:Size="11"/></Style>';
        foreach ($styles as $id => $inner) {
            $xml .= '<Style ss:ID="'.self::esc($id).'">'.$inner.'</Style>';
        }
        $xml .= '</Styles>';

        foreach ($sheets as $sheet) {
            $rtl = $sheet['rtl'] ?? true;
            $rtlAttr = $rtl ? ' ss:RightToLeft="1"' : '';
            $xml .= '<Worksheet ss:Name="'.self::esc($sheet['name']).'"'.$rtlAttr.'>';
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
            $xml .= '<x:WorksheetOptions>';
            if ($rtl) {
                $xml .= '<x:DisplayRightToLeft/>';
            }
            if (! empty($sheet['freeze'])) {
                $xml .= '<x:FreezePanes/><x:FrozenNoSplit/><x:SplitHorizontal>1</x:SplitHorizontal><x:TopRowBottomPane>1</x:TopRowBottomPane><x:ActivePane>2</x:ActivePane>';
            }
            $xml .= '</x:WorksheetOptions></Worksheet>';
        }

        $xml .= '</Workbook>';

        return $xml;
    }

    public static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
