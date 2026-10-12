import pandas as pd

excel_file = "Disiplin Pro tata tertib siswa V-1.xlsx"
df = pd.read_excel(excel_file, sheet_name='SURAT', nrows=30)
print("--- SHEET: SURAT ---")
print(df.to_string(index=False, na_rep=''))
