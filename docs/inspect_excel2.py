import pandas as pd

excel_file = "Disiplin Pro tata tertib siswa V-1.xlsx"
sheets = ['SURAT', 'LAP_BULANAN', 'REKAP_WALIKELAS', 'ANALISIS']

for sheet in sheets:
    df = pd.read_excel(excel_file, sheet_name=sheet, nrows=15)
    print(f"\n--- SHEET: {sheet} ---")
    print(df.to_string(index=False, na_rep=''))
