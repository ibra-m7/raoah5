import json
import sys

import xlrd


def main() -> None:
    if len(sys.argv) < 3:
        print("Usage: read_binary_xls.py <source.xls> <output.json>", file=sys.stderr)
        sys.exit(1)

    source, output = sys.argv[1], sys.argv[2]
    workbook = xlrd.open_workbook(source)
    sheet = workbook.sheet_by_index(0)
    rows = [sheet.row_values(i) for i in range(sheet.nrows)]

    with open(output, "w", encoding="utf-8") as handle:
        json.dump(rows, handle, ensure_ascii=False)


if __name__ == "__main__":
    main()
