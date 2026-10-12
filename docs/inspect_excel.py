import pandas as pd
import json

excel_file = "Disiplin Pro tata tertib siswa V-1.xlsx"

# get sheet names
xls = pd.ExcelFile(excel_file)
print("SHEETS:", xls.sheet_names)

sheets_to_inspect = ['SURAT', 'LAP_BULANAN', 'REKAP_WALIKELAS', 'ANALISIS', 'POIN_SISWA', 'DATA_SISWA', 'MASTER_PELANGGARAN', 'MASTER_PRESTASI', 'MASTER_SANKSI', 'TUTUP_SEMESTER', 'SETUP', 'REF']

for sheet in sheets_to_inspect:
    if sheet in xls.sheet_names:
        df = pd.read_excel(excel_file, sheet_name=sheet, nrows=20)
        print(f"\n--- SHEET: {sheet} ---")
        # Print column names
        print("COLUMNS:", df.columns.tolist())
        # Print first 5 rows to understand the format (sometimes the actual header is in row 2 or 3)
        print(df.head(5).to_string(index=False, na_rep=''))
    else:
        print(f"\n--- SHEET: {sheet} NOT FOUND ---")

